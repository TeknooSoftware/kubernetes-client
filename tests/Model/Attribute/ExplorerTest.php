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

namespace Teknoo\Tests\Kubernetes\Model\Attribute;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Teknoo\Kubernetes\Model\Attribute\Explorer;
use Teknoo\Kubernetes\Model\Model;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ExplorerTest extends PHPUnitTestCase
{
    private Model|MockObject|null $model = null;

    private array $attributes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->attributes = [
            'metadata' => [
                'name' => 'foo'
            ],
            'spec' => [
                'bar' => 'foo',
                'foo' => [
                    'bar' => 'foo',
                ],
            ],
        ];
    }

    private function createModel(): Model
    {
        return $this->model ??= $this->createMock(Model::class);
    }

    private function createExplorer(): Explorer
    {
        return new Explorer(
            $this->createModel(),
            $this->attributes
        );
    }

    public function testGetModel(): void
    {
        $this->assertInstanceOf(Model::class, $this->createExplorer()->getModel());
    }

    public function testGet(): void
    {
        $explorer = $this->createExplorer();
        $this->assertNull($explorer->bar);
        $this->assertInstanceOf(Explorer::class, $explorer->metadata);
        $this->assertEquals('foo', $explorer->metadata->name);
        $this->assertInstanceOf(Explorer::class, $explorer->spec);
        $this->assertEquals('foo', $explorer->spec->bar);
        $this->assertInstanceOf(Explorer::class, $explorer->spec->foo);
    }

    public function testSet(): void
    {
        $explorer = $this->createExplorer();
        $explorer->metadata->name = 'bar';
        $this->assertEquals('bar', $this->attributes['metadata']['name']);
        $this->assertEquals('foo', $explorer->spec->bar);
        $explorer->spec->bar = null;
        $this->assertArrayNotHasKey('bar', $this->attributes['spec']);
    }

    public function testUnset(): void
    {
        $explorer = $this->createExplorer();
        $this->assertEquals('foo', $explorer->spec->bar);
        unset($explorer->spec->bar);
        $this->assertArrayNotHasKey('bar', $this->attributes['spec']);
    }

    public function testIsset(): void
    {
        $explorer = $this->createExplorer();
        $this->assertTrue(isset($explorer->metadata));
        $this->assertTrue(isset($explorer->metadata->name));
        $this->assertFalse(isset($explorer->metadata->namespace));
        $this->assertFalse(isset($explorer->metadata2->name));
    }

    public function testToArray(): void
    {
        $this->assertEquals($this->attributes, $this->createExplorer()->toArray());
    }
}