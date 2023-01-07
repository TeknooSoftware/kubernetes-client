<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Collection;

use Illuminate\Support\Collection as IlluminateCollection;
use LogicException;
use Teknoo\Kubernetes\Model\Model;

use function is_a;
use function sprintf;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 *
 * @template T of Model
 * @extends IlluminateCollection<int, T>
 */
abstract class Collection extends IlluminateCollection
{
    /**
     * @var class-string<T>
     */
    protected static ?string $modelClassName = null;

    /**
     * @param array<int, T> $items
     */
    public function __construct(array $items)
    {
        parent::__construct($this->getItems($items));
    }

    /**
     * @return class-string<T>
     */
    public static function getModelClass(): string
    {
        if (null === static::$modelClassName) {
            throw new LogicException(
                "Error, Model class name or getItems must be defined for the collection " . static::class
            );
        }

        if (!is_a(static::$modelClassName, Model::class, true)) {
            throw new LogicException(
                sprintf(
                    "Error, Model %s must implements %s to be use by %s",
                    static::$modelClassName,
                    Model::class,
                    static::class,
                ),
            );
        }

        return static::$modelClassName;
    }

    /**
     * @param array<int, T> $items
     * @return array<int, T>
     */
    protected function getItems(array &$items): array
    {
        $modelClassName = self::getModelClass();

        $final = [];
        foreach ($items as &$item) {
            if (is_a($item, $modelClassName)) {
                $final[] = $item;

                continue;
            }

            $final[] = new $modelClassName($item);
        }

        return $final;
    }
}
