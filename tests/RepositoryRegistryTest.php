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

namespace Teknoo\Tests\Kubernetes;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\Kubernetes\Repository\JobRepository;
use Teknoo\Kubernetes\RepositoryRegistry;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(RepositoryRegistry::class)]
class RepositoryRegistryTest extends TestCase
{
    private const string TEST_CLASS = JobRepository::class;

    public function testBuiltinRepositories(): void
    {
        $registry = new RepositoryRegistry();

        $this->assertCount(30, $registry);
    }

    public function testAddRepository(): void
    {
        $registry = new RepositoryRegistry();

        $this->assertArrayNotHasKey('test', $registry);

        $registry['test'] = self::TEST_CLASS;

        $this->assertArrayHasKey('test', $registry);
        $this->assertEquals(self::TEST_CLASS, $registry['test']);
    }

    public function testAddRepositoryWithNonClassString(): void
    {
        $registry = new RepositoryRegistry();

        $this->assertArrayNotHasKey('test', $registry);

        $this->expectException(InvalidArgumentException::class);
        $registry['test'] = 1234567890;
    }

    public function testAddRepositoryWithNonRepositoryClassString(): void
    {
        $registry = new RepositoryRegistry();

        $this->assertArrayNotHasKey('test', $registry);

        $this->expectException(InvalidArgumentException::class);
        $registry['test'] = stdClass::class;;
    }

    public function testRemoveRepository(): void
    {
        $registry = new RepositoryRegistry();

        unset($registry['roles']);
        $this->assertArrayNotHasKey('roles', $registry);
        $this->assertNull($registry['roles']);
    }
}
