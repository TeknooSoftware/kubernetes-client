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

namespace Teknoo\Tests\Kubernetes\Collection;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use Teknoo\Kubernetes\Collection\Collection;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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

        $this->assertIsArray($items);
        $this->assertCount(3, $items);

        $this->assertContainsOnlyInstancesOf($this->getModelClassName(), $items);
    }

    public function testHasNext(): void
    {
        $this->assertFalse($this->getCollection()->hasNext());
        $this->assertTrue($this->getCollection(['foo' => 'bar'], 'foo')->hasNext());
    }

    public function testGetQuery(): void
    {
        $this->assertEmpty($this->getCollection()->getQuery());
        $this->assertEquals(['foo' => 'bar'], $this->getCollection(['foo' => 'bar'], 'foo')->getQuery());
    }

    public function testGetContinueToken(): void
    {
        $this->assertEmpty($this->getCollection()->getContinueToken());
        $this->assertEquals('foo', $this->getCollection(['foo' => 'bar'], 'foo')->getContinueToken());
    }

    public function testContinue(): void
    {
        $this->assertNotInstanceOf(\Teknoo\Kubernetes\Collection\Collection::class, $this->getCollection()->continue());
        $this->assertInstanceOf(Collection::class, $this->getCollection(['foo' => 'bar'], 'foo')->continue());
    }
}
