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

namespace Teknoo\Tests\Kubernetes;

use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\RepositoryRegistry;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\RepositoryRegistry
 */
class RepositoryRegistryTest extends TestCase
{
    private const TEST_CLASS = '\Example\Class';

    public function testBuiltinRepositories(): void
    {
        $registry = new RepositoryRegistry();

        self::assertCount(28, $registry);
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
