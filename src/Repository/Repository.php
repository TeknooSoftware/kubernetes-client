<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Repository;

use LogicException;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Repository\Exception\NoItemsException;

use function implode;
use function json_encode;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @template    T of \Teknoo\Kubernetes\Model\Model
 */
abstract class Repository
{
    protected string $uri = '';

    protected bool $namespace = true;

    /**
     * @var array<string, string|null>
     */
    protected array $labelSelector = [];

    /**
     * @var array<string, string|null>
     */
    protected array $inequalityLabelSelector = [];

    /**
     * @var array<string, string|null>
     */
    protected array $fieldSelector = [];

    /**
     * @var array<string, string|null>
     */
    protected array $inequalityFieldSelector = [];

    protected static ?string $collectionClassName = null;

    public function __construct(
        protected Client $client
    ) {
    }

    /**
     * @param array<string, string|null> $query
     * @return array<string, string|null>
     */
    protected function sendRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = [],
        bool $namespace = true,
        ?PatchType $patchType = null,
    ): array {
        $apiVersion = self::getApiVersion();
        if ('v1' === $apiVersion) {
            $apiVersion = null;
        }

        if (null !== $patchType) {
            $this->client->setPatchType($patchType);
        }

        return $this->client->sendRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
        );
    }

    protected static function getApiVersion(): ?string
    {
        $collectionClass = self::getCollectionName();
        $modelClass = $collectionClass::getModelClass();

        return $modelClass::getApiVersion();
    }

    /**
     * @return array<string, string|null>
     */
    public function create(Model $model): array
    {
        return $this->sendRequest(
            method: RequestMethod::Post,
            uri: '/' . $this->uri,
            body: $model->getSchema(),
            namespace: $this->namespace
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function update(Model $model): array
    {
        return $this->sendRequest(
            method: RequestMethod::Put,
            uri: '/' . $this->uri . '/' . $model->getMetadata('name'),
            body: $model->getSchema(),
            namespace: $this->namespace
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function patch(Model $model): array
    {
        return $this->sendRequest(
            method: RequestMethod::Patch,
            uri: '/' . $this->uri . '/' . $model->getMetadata('name'),
            body: $model->getSchema(),
            namespace: $this->namespace
        );
    }

    /**
     * @param array<string, string|null> $patch
     * @return array<string, string|null>
     */
    public function applyJsonPatch(Model $model, array $patch): array
    {
        $patch = json_encode(value: $patch, flags: JSON_THROW_ON_ERROR);

        return $this->sendRequest(
            method: RequestMethod::Patch,
            uri: '/' . $this->uri . '/' . $model->getMetadata('name'),
            body: $patch,
            namespace: $this->namespace,
            patchType: PatchType::Json,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function apply(Model $model): array
    {
        if ($this->exists((string) $model->getMetadata("name"))) {
            return $this->patch($model);
        }

        return $this->create($model);
    }

    /**
     * @return array<string, string|null>
     */
    public function delete(Model $model, DeleteOptions $options = null): array
    {
        return $this->deleteByName((string) $model->getMetadata('name'), $options);
    }

    /**
     * @return array<string, string|null>
     */
    public function deleteByName(string $name, DeleteOptions $options = null): array
    {
        return $this->sendRequest(
            method: RequestMethod::Delete,
            uri: '/' . $this->uri . '/' . $name,
            body: $options?->getSchema(),
            namespace: $this->namespace
        );
    }

    /**
     * @param array<string, string|null> $labelSelector
     * @param array<string, string|null> $inequalityLabelSelector
     * @return Repository<T>
     */
    public function setLabelSelector(array $labelSelector, array $inequalityLabelSelector = []): Repository
    {
        $this->labelSelector           = $labelSelector;
        $this->inequalityLabelSelector = $inequalityLabelSelector;

        return $this;
    }

    protected function getLabelSelectorQuery(): string
    {
        $parts = [];
        foreach ($this->labelSelector as $key => $value) {
            if (null === $value) {
                $parts[] = $key;
            } else {
                $parts[] = ($key . '=' . $value);
            }
        }

        // If any inequality search terms are set, add them to the parts array
        foreach ($this->inequalityLabelSelector as $key => $value) {
            $parts[] = $key . '!=' . $value;
        }

        return implode(',', $parts);
    }

    /**
     * @param array<string, string|null> $fieldSelector
     * @param array<string, string|null> $inequalityFieldSelector
     * @return Repository<T>
     */
    public function setFieldSelector(array $fieldSelector, array $inequalityFieldSelector = []): Repository
    {
        $this->fieldSelector           = $fieldSelector;
        $this->inequalityFieldSelector = $inequalityFieldSelector;

        return $this;
    }

    protected function getFieldSelectorQuery(): string
    {
        $parts = [];
        foreach ($this->fieldSelector as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        // If any inequality search terms are set, add them to the parts array
        foreach ($this->inequalityFieldSelector as $key => $value) {
            $parts[] = $key . '!=' . $value;
        }

        return implode(',', $parts);
    }

    /**
     * @return Repository<T>
     */
    protected function resetParameters(): self
    {
        $this->labelSelector = [];
        $this->fieldSelector = [];

        return $this;
    }

    /**
     * @param array<string, string|null> $query
     * @return Collection<T>
     */
    public function find(array $query = []): Collection
    {
        $query = array_filter(
            array_merge(
                [
                    'labelSelector' => $this->getLabelSelectorQuery(),
                    'fieldSelector' => $this->getFieldSelectorQuery(),
                ],
                $query
            ),
            static fn($value): bool => !empty($value)
        );

        $this->resetParameters();

        $response = $this->sendRequest(RequestMethod::Get, '/' . $this->uri, $query, null, $this->namespace);

        return $this->createCollection($response);
    }

    public function first(): ?Model
    {
        return $this->find()->first();
    }

    /**
     * @param array<string, string|null> $query
     * @return Repository<T>
     */
    public function stream(Model $model, StreamingParser $parser, array $query = []): self
    {
        $this->setFieldSelector(
            [
                'metadata.name' => $model->getMetadata('name'),
            ]
        );

        $query = array_filter(
            array_merge(
                [
                    'watch'          => '1',
                    'timeoutSeconds' => '30',
                    'labelSelector'  => $this->getLabelSelectorQuery(),
                    'fieldSelector'  => $this->getFieldSelectorQuery(),
                ],
                $query
            ),
            static fn($value): bool => !empty($value)
        );

        $this->resetParameters();

        $apiVersion = self::getApiVersion();
        if ('v1' === $apiVersion) {
            $apiVersion = null;
        }

        $response = $this->client->sendStreamableRequest(
            method: RequestMethod::Get,
            uri: '/' . $this->uri,
            query: $query,
            namespace: $this->namespace,
            apiVersion: $apiVersion,
        );

        $parser->parse($response->getBody());

        return $this;
    }

    public function exists(string $name): bool
    {
        $this->resetParameters();
        return null !== $this->setFieldSelector(['metadata.name' => $name])->first();
    }

    /**
     * @return class-string<Collection<T>>
     */
    private static function getCollectionName(): string
    {
        if (null === static::$collectionClassName) {
            throw new LogicException(
                "Error, Model class name or getItems must be defined for the collection " . static::class
            );
        }

        if (!is_a(static::$collectionClassName, Collection::class, true)) {
            throw new LogicException(
                sprintf(
                    "Error, Collection %s must implements %s to be use by %s",
                    static::$collectionClassName,
                    Collection::class,
                    static::class,
                ),
            );
        }

        return static::$collectionClassName;
    }

    /**
     * @param array<string, string|null> $response
     * @return Collection<T>
     */
    protected function createCollection(array $response): Collection
    {
        $collectionClassName = self::getCollectionName();

        if (!isset($response['items'])) {
            throw new NoItemsException('Error, no items returned by the Kubernetes API');
        }

        return new $collectionClassName($response['items']);
    }
}
