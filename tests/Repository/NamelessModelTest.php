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

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Node;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\NodeRepository;
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
#[CoversClass(NodeRepository::class)]
class NamelessModelTest extends TestCase
{
    private function createClientExpectingNoRequest(): Client
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('sendRequest');
        $client->expects($this->never())->method('sendStringableRequest');
        $client->expects($this->never())->method('sendStreamableRequest');

        return $client;
    }

    /**
     * @return iterable<string, array{0: callable(PodRepository, Pod): mixed}>
     */
    public static function provideNamedOperations(): iterable
    {
        yield 'update' => [static fn (PodRepository $r, Pod $p): array => $r->update($p)];
        yield 'patch' => [static fn (PodRepository $r, Pod $p): array => $r->patch($p)];
        yield 'applyJsonPatch' => [static fn (PodRepository $r, Pod $p): array => $r->applyJsonPatch($p, [])];
        yield 'apply' => [static fn (PodRepository $r, Pod $p): array => $r->apply($p)];
        yield 'delete' => [static fn (PodRepository $r, Pod $p): array => $r->delete($p)];
        yield 'logs' => [static fn (PodRepository $r, Pod $p): string => $r->logs($p)];
        yield 'exec' => [static fn (PodRepository $r, Pod $p): string => $r->exec($p)];
    }

    /**
     * @param callable(PodRepository, Pod): mixed $operation
     */
    #[DataProvider('provideNamedOperations')]
    public function testAModelWithoutNameIsRefused(callable $operation): void
    {
        $repository = new PodRepository($this->createClientExpectingNoRequest());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/metadata\.name/');
        $operation($repository, new Pod(['spec' => ['foo' => 'bar']]));
    }

    /**
     * @param callable(PodRepository, Pod): mixed $operation
     */
    #[DataProvider('provideNamedOperations')]
    public function testAModelWithAnEmptyNameIsRefused(callable $operation): void
    {
        $repository = new PodRepository($this->createClientExpectingNoRequest());

        $this->expectException(InvalidArgumentException::class);
        $operation($repository, new Pod(['metadata' => ['name' => '']]));
    }

    public function testDeleteByNameRefusesAnEmptyName(): void
    {
        $repository = new PodRepository($this->createClientExpectingNoRequest());

        $this->expectException(InvalidArgumentException::class);
        $repository->deleteByName('');
    }

    public function testExistsRefusesAnEmptyName(): void
    {
        $repository = new PodRepository($this->createClientExpectingNoRequest());

        $this->expectException(InvalidArgumentException::class);
        $repository->exists('');
    }

    public function testProxyRefusesANodeWithoutName(): void
    {
        $repository = new NodeRepository($this->createClientExpectingNoRequest());

        $this->expectException(InvalidArgumentException::class);
        $repository->proxy(new Node([]), RequestMethod::Get, 'metrics');
    }

    public function testAModelNamedZeroIsAccepted(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(RequestMethod::Delete, '/pods/0')
            ->willReturn(['kind' => 'Status']);

        $repository = new PodRepository($client);

        $this->assertSame(['kind' => 'Status'], $repository->delete(new Pod(['metadata' => ['name' => '0']])));
    }
}
