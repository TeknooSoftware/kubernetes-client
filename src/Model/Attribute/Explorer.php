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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Model\Attribute;

use Teknoo\Kubernetes\Model\Model;

use function is_array;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Explorer
{
    /**
     * @param array<string, string|array<string, mixed>> $attributes
     */
    final public function __construct(
        private Model $model,
        private array &$attributes,
    ) {
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function __get(string $name): static|string|null
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

        if (is_array($this->attributes[$name])) {
            return new static(
                model: $this->model,
                attributes: $this->attributes[$name],
            );
        }

        return $this->attributes[$name];
    }

    /**
     * @param string|array<string, mixed>|null $value
     */
    public function __set(string $name, string|array|null $value): void
    {
        if (null === $value) {
            unset($this->attributes[$name]);

            return;
        }

        $this->attributes[$name] = $value;
    }

    public function __unset(string $name): void
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     *  @return array<string, string|array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
