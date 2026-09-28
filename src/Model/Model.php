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

namespace Teknoo\Kubernetes\Model;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use JsonException;
use Override;
use Stringable;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Attribute\Explorer;

use function array_merge;
use function basename;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function str_replace;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @implements Arrayable<string, string|array<string, mixed>>
 */
abstract class Model implements Arrayable, Stringable
{
    protected static string $apiVersion = 'v1';

    protected bool $pluralKind = false;

    /**
     * @var array<string, string|array<string, mixed>>
     */
    protected array $schema = [];

    /**
     * @var array<string, string|array<string, mixed>>
     */
    protected array $attributes = [];

    /**
     * @param string|array<string, string|array<int|string, mixed>> $attributes
     * @throws InvalidArgumentException
     */
    public function __construct(array|string $attributes = [], FileFormat $format = FileFormat::Array)
    {
        try {
            $decoded = match (true) {
                FileFormat::Array === $format && !is_array($attributes) => throw new InvalidArgumentException(
                    'Array attributes must be provided as an array.'
                ),
                FileFormat::Array === $format && is_array($attributes) => $attributes,
                FileFormat::Json === $format && !is_string($attributes) => throw new InvalidArgumentException(
                    'JSON attributes must be provided as a JSON encoded string.'
                ),
                FileFormat::Json === $format && is_string($attributes) => json_decode(
                    json: $attributes,
                    associative: true,
                    depth: 512,
                    flags: JSON_THROW_ON_ERROR
                ),
                FileFormat::Yaml === $format && !is_string($attributes) => throw new InvalidArgumentException(
                    'YAML attributes must be provided as a YAML encoded string.'
                ),
                FileFormat::Yaml === $format && is_string($attributes) => Yaml::parse($attributes),
            };
        } catch (JsonException $jsonException) {
            throw new InvalidArgumentException(
                message: 'Failed to parse JSON encoded attributes: ' . $jsonException->getMessage(),
                previous: $jsonException
            );
        } catch (YamlParseException $yamlParseException) {
            throw new InvalidArgumentException(
                message: 'Failed to parse YAML encoded attributes: ' . $yamlParseException->getMessage(),
                previous: $yamlParseException
            );
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Attributes must decode to an array.');
        }

        /** @var array<string, string|array<string, mixed>> $decoded */
        $this->attributes = $decoded;
    }

    #[Override]
    public function toArray(): array
    {
        return $this->attributes;
    }

    #[Override]
    public function __toString(): string
    {
        return $this->getSchema();
    }

    public function getMetadata(string $key): ?string
    {
        $metadata = $this->attributes['metadata'] ?? null;
        if (!is_array($metadata)) {
            return null;
        }

        $value = $metadata[$key] ?? null;
        if (is_string($value) && '' !== $value) {
            return $value;
        }

        return null;
    }

    public function explore(): Explorer
    {
        $that = clone $this;
        return new Explorer(
            model: $that,
            attributes: $that->attributes,
        );
    }

    public function getSchema(): string
    {
        if (!isset($this->schema['kind'])) {
            $this->schema['kind'] = basename(str_replace('\\', '/', static::class));
            if ($this->pluralKind) {
                $this->schema['kind'] .= 's';
            }
        }

        if (!isset($this->schema['apiVersion'])) {
            $this->schema['apiVersion'] = static::$apiVersion;
        }

        $schema = array_merge($this->schema, $this->attributes);

        $jsonSchema = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        // Fix for issue #37, can't use JSON_FORCE_OBJECT as the encoding breaks arrays of objects,
        // for example port mappings. Empty arrays are maps (labels, annotations...), encoded as JSON objects.
        // The closing quote of the key is required before ": []", so a string value holding ": []" is not altered.
        return str_replace('": []', '": {}', $jsonSchema);
    }

    public static function getApiVersion(): string
    {
        return static::$apiVersion;
    }

    /**
     * Returns a copy of this model whose attributes are replaced by the result of the modifier; this model is not
     * altered, so the returned copy must be used.
     */
    public function updateModel(callable $modifier): self
    {
        $that = clone $this;
        $that->attributes = $modifier($this->attributes);

        return $that;
    }
}
