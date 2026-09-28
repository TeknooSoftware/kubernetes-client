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
class SelectorResetTest extends TestCase
{
    /**
     * @var array<int, array<string, int|string|null>>
     */
    private array $queries = [];

    private function createRepository(int $expectedRequests): PodRepository
    {
        $this->queries = [];
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->exactly($expectedRequests))
            ->method('sendRequest')
            ->willReturnCallback(
                function (RequestMethod $method, string $uri, array $query = []): array {
                    $this->queries[] = $query;

                    return ['items' => [['metadata' => ['name' => 'foo']]]];
                }
            );

        return new PodRepository($client);
    }

    public function testInequalityLabelSelectorDoesNotLeakIntoTheNextFind(): void
    {
        $repository = $this->createRepository(2);

        $repository->setLabelSelector(['app' => 'web'], ['env' => 'prod'])->find();
        $repository->find();

        $this->assertSame('app=web,env!=prod', $this->queries[0]['labelSelector']);
        $this->assertArrayNotHasKey('labelSelector', $this->queries[1]);
    }

    public function testInequalityFieldSelectorDoesNotLeakIntoExists(): void
    {
        $repository = $this->createRepository(2);

        $repository->setFieldSelector([], ['status.phase' => 'Failed'])->find();
        $this->assertTrue($repository->exists('foo'));

        $this->assertSame('status.phase!=Failed', $this->queries[0]['fieldSelector']);
        $this->assertSame('metadata.name=foo', $this->queries[1]['fieldSelector']);
    }

    public function testSelectorsAreResetAfterContinue(): void
    {
        $repository = $this->createRepository(2);

        $repository->setLabelSelector([], ['env' => 'prod'])->continue(['limit' => 1], 'token');
        $repository->find();

        $this->assertSame(['limit' => 1, 'continue' => 'token'], $this->queries[0]);
        $this->assertSame([], $this->queries[1]);
    }

    public function testSelectorsAreResetAfterFirst(): void
    {
        $repository = $this->createRepository(2);

        $repository->setLabelSelector(['app' => 'web'], ['env' => 'prod'])->first();
        $repository->first();

        $this->assertSame('app=web,env!=prod', $this->queries[0]['labelSelector']);
        $this->assertSame(['limit' => 1], $this->queries[1]);
    }
}
