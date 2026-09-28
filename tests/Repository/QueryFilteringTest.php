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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;
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
class QueryFilteringTest extends TestCase
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
            ->willReturn(['items' => []]);

        return new PodRepository($client);
    }

    public function testZeroValuesAreKept(): void
    {
        $repository = $this->createRepository(['resourceVersion' => '0', 'limit' => 0]);
        $repository->find(['resourceVersion' => '0', 'limit' => 0]);
    }

    public function testNullAndEmptyStringValuesAreDropped(): void
    {
        $repository = $this->createRepository(['foo' => 'bar']);
        $repository->find(['foo' => 'bar', 'labelSelector' => '', 'continue' => null]);
    }

    public function testAZeroTimeoutIsKeptWhenWatching(): void
    {
        $stream = $this->createStub(StreamInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('sendStreamableRequest')
            ->with(
                RequestMethod::Get,
                '/pods',
                ['watch' => '1', 'timeoutSeconds' => '0', 'fieldSelector' => 'metadata.name=p', 'resourceVersion' => '0'],
            )
            ->willReturn($response);

        $repository = new PodRepository($client);
        $repository->stream(
            new Pod(['metadata' => ['name' => 'p']]),
            $this->createStub(StreamingParser::class),
            ['timeoutSeconds' => '0', 'resourceVersion' => '0'],
        );
    }
}
