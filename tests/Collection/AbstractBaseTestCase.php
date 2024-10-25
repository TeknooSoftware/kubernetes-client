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

namespace Teknoo\Tests\Kubernetes\Collection;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use Teknoo\Kubernetes\Collection\Collection;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
abstract class AbstractBaseTestCase extends PHPUnitTestCase
{
    abstract protected function getCollection(?array $query = null, ?string $continue = null): Collection;

    abstract protected function getModelClassName(): string;

    public function testGetItems(): void
    {
        $items = $this->getCollection()->toArray();

        self::assertIsArray($items);
        self::assertCount(3, $items);

        foreach ($items as $item) {
            self::assertInstanceOf(
                $this->getModelClassName(),
                $item,
            );
        }
    }

    public function testHasNext()
    {
        self::assertFalse($this->getCollection()->hasNext());
        self::assertTrue($this->getCollection(['foo' => 'bar'], 'foo')->hasNext());
    }

    public function testGetQuery()
    {
        self::assertEmpty($this->getCollection()->getQuery());
        self::assertEquals(['foo' => 'bar'], $this->getCollection(['foo' => 'bar'], 'foo')->getQuery());
    }

    public function testGetContinueToken()
    {
        self::assertEmpty($this->getCollection()->getContinueToken());
        self::assertEquals('foo', $this->getCollection(['foo' => 'bar'], 'foo')->getContinueToken());
    }

    public function testContinue()
    {
        self::assertNull($this->getCollection()->continue());
        self::assertInstanceOf(
            Collection::class,
            $this->getCollection(['foo' => 'bar'], 'foo')->continue(),
        );
    }
}
