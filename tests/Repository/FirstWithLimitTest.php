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
 *
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\Kubernetes\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Repository::class)]
#[CoversClass(PodRepository::class)]
class FirstWithLimitTest extends TestCase
{
    /**
     * @param array<string, int|string|null> $expectedQuery
     */
    private function createRepository(array $expectedQuery): PodRepository
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(RequestMethod::Get, '/pods', $expectedQuery)
            ->willReturn(['items' => [['metadata' => ['name' => 'foo']]]]);

        return new PodRepository($client);
    }

    public function testFirstAsksASingleItem(): void
    {
        $repository = $this->createRepository(['limit' => 1]);

        $this->assertInstanceOf(Pod::class, $repository->first());
    }

    public function testFirstKeepsTheSelectors(): void
    {
        $repository = $this->createRepository(['labelSelector' => 'app=web', 'limit' => 1]);

        $this->assertInstanceOf(Pod::class, $repository->setLabelSelector(['app' => 'web'])->first());
    }

    public function testExistsAsksASingleItem(): void
    {
        $repository = $this->createRepository(['fieldSelector' => 'metadata.name=foo', 'limit' => 1]);

        $this->assertTrue($repository->exists('foo'));
    }
}
