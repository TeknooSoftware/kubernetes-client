<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Repository;

use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Exceptions\TimeExceededAboutContinueException;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Repository\Exception\NoItemsException;

use function array_filter;
use function array_merge;
use function implode;
use function is_a;
use function rawurlencode;
use function sprintf;
use function json_encode;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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

    /**
     * @var class-string<Collection<T>>|null
     */
    protected static ?string $collectionClassName = null;

    /**
     * Repositories whose collection class has already been validated, to avoid a reflection on every request.
     *
     * @var array<class-string, true>
     */
    private static array $validatedCollections = [];

    public function __construct(
        protected Client $client
    ) {
    }

    /**
     * @param array<string, int|string|null> $query
     * @param StreamInterface|string|array<string, mixed>|null $body
     * @return array<string, string|null>
     */
    protected function sendRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = [],
        bool $namespace = true,
        ?PatchType $patchType = null,
    ): array {
        $apiVersion = static::getApiVersion();
        if ('v1' === $apiVersion) {
            $apiVersion = null;
        }

        return $this->client->sendRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: $namespace,
            apiVersion: $apiVersion,
            patchType: $patchType,
        );
    }

    /**
     * The patch type used by patch() and apply() for this repository. Null means the default type configured on
     * the client. Repositories of custom resources must return PatchType::Merge, as CRDs do not support the
     * strategic merge patch.
     */
    protected function getPatchType(): ?PatchType
    {
        return null;
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
            uri: $this->getResourceUri($this->requireName($model)),
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
            uri: $this->getResourceUri($this->requireName($model)),
            body: $model->getSchema(),
            namespace: $this->namespace,
            patchType: $this->getPatchType(),
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
            uri: $this->getResourceUri($this->requireName($model)),
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
        if ($this->exists($this->requireName($model))) {
            return $this->patch($model);
        }

        return $this->create($model);
    }

    /**
     * @return array<string, string|null>
     */
    public function delete(Model $model, ?DeleteOptions $options = null): array
    {
        return $this->deleteByName($this->requireName($model), $options);
    }

    /**
     * @return array<string, string|null>
     */
    public function deleteByName(string $name, ?DeleteOptions $options = null): array
    {
        return $this->sendRequest(
            method: RequestMethod::Delete,
            uri: $this->getResourceUri($this->requireNonEmptyName($name)),
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
        $this->inequalityLabelSelector = [];
        $this->fieldSelector = [];
        $this->inequalityFieldSelector = [];

        return $this;
    }

    /**
     * @param array<string, string|null> $query
     * @return Collection<T>
     */
    public function find(array $query = [], ?int $limit = null): Collection
    {
        $query = array_filter(
            array_merge(
                [
                    'labelSelector' => $this->getLabelSelectorQuery(),
                    'fieldSelector' => $this->getFieldSelectorQuery(),
                ],
                $query
            ),
            static fn ($value): bool => null !== $value && '' !== $value
        );

        if (null !== $limit) {
            $query['limit'] = $limit;
        }

        $this->resetParameters();

        $response = $this->sendRequest(
            RequestMethod::Get,
            '/' . $this->uri,
            $query,
            null,
            $this->namespace,
        );

        return $this->createCollection($response, $query);
    }

    /**
     * @param array<string, string|int|null> $query
     * @return Collection<T>
     */
    public function continue(array $query, string $continue): Collection
    {
        $query['continue'] = $continue;

        $this->resetParameters();

        try {
            $response = $this->sendRequest(
                RequestMethod::Get,
                '/' . $this->uri,
                $query,
                null,
                $this->namespace,
            );
        } catch (ApiServerException $error) {
            if (410 === $error->getCode()) {
                throw new TimeExceededAboutContinueException(
                    message: $error->getMessage(),
                    code: $error->getCode(),
                    previous: $error,
                );
            }

            throw $error;
        }

        return $this->createCollection($response, $query);
    }

    public function first(): ?Model
    {
        // Only the first item is needed, the API is asked for a single item
        return $this->find(limit: 1)->first();
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
            static fn ($value): bool => null !== $value && '' !== $value
        );

        $this->resetParameters();

        $apiVersion = static::getApiVersion();
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
        return null !== $this->setFieldSelector(['metadata.name' => $this->requireNonEmptyName($name)])->first();
    }

    /**
     * The uri of a resource of this repository, its name being encoded to stay a single path segment.
     */
    protected function getResourceUri(string $name): string
    {
        return '/' . $this->uri . '/' . rawurlencode($name);
    }

    /**
     * Returns the name of the model, required to target a resource (update, patch, delete, logs...).
     *
     * @throws InvalidArgumentException when the model has no metadata.name
     */
    protected function requireName(Model $model): string
    {
        return $this->requireNonEmptyName((string) $model->getMetadata('name'));
    }

    /**
     * @throws InvalidArgumentException when the name is empty: the request would target the whole collection
     */
    protected function requireNonEmptyName(string $name): string
    {
        if ('' === $name) {
            throw new InvalidArgumentException(
                'Error, a non empty metadata.name is required to target a resource with ' . static::class
            );
        }

        return $name;
    }

    /**
     * @return class-string<Collection<T>>
     */
    private static function getCollectionName(): string
    {
        if (isset(self::$validatedCollections[static::class]) && null !== static::$collectionClassName) {
            return static::$collectionClassName;
        }

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

        self::$validatedCollections[static::class] = true;

        return static::$collectionClassName;
    }

    /**
     * @param array<string, string|null|array<string|string>> $response
     * @param array<string, int|string|null> $query
     * @return Collection<T>
     */
    protected function createCollection(array $response, array &$query): Collection
    {
        $collectionClassName = self::getCollectionName();

        if (!isset($response['items'])) {
            throw new NoItemsException('Error, no items returned by the Kubernetes API');
        }

        return new $collectionClassName(
            items: $response['items'],
            repository: $this,
            query: $query,
            continueToken: $response['metadata']['continue'] ?? null,
        );
    }
}
