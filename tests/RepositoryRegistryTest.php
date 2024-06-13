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

namespace Teknoo\Tests\Kubernetes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\RepositoryRegistry;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(RepositoryRegistry::class)]
class RepositoryRegistryTest extends TestCase
{
    private const TEST_CLASS = '\Example\Class';

    public function testBuiltinRepositories(): void
    {
        $registry = new RepositoryRegistry();

        self::assertCount(30, $registry);
    }

    public function testAddRepository(): void
    {
        $registry = new RepositoryRegistry();

        self::assertFalse(isset($registry['test']));

        $registry['test'] = self::TEST_CLASS;

        self::assertTrue(isset($registry['test']));
        self::assertEquals(self::TEST_CLASS, $registry['test']);
    }

    public function testRemoveRepository(): void
    {
        $registry = new RepositoryRegistry();

        unset($registry['roles']);
        self::assertFalse(isset($registry['roles']));
        self::assertNull($registry['roles']);
    }
}
