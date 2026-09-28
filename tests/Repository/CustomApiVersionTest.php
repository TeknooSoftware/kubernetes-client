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
class CustomApiVersionTest extends TestCase
{
    private function createRepository(Client $client): PodRepository
    {
        return new class ($client) extends PodRepository {
            protected static function getApiVersion(): ?string
            {
                return 'custom.io/v1';
            }
        };
    }

    public function testAnOverriddenApiVersionIsUsedByRequests(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(RequestMethod::Get, '/pods', [], null, true, 'custom.io/v1')
            ->willReturn(['items' => []]);

        $this->assertCount(0, $this->createRepository($client)->find());
    }

    public function testAnOverriddenApiVersionIsUsedWhenWatching(): void
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
                ['watch' => '1', 'timeoutSeconds' => '30', 'fieldSelector' => 'metadata.name=p'],
                null,
                true,
                'custom.io/v1',
            )
            ->willReturn($response);

        $this->createRepository($client)->stream(
            new Pod(['metadata' => ['name' => 'p']]),
            $this->createStub(StreamingParser::class),
        );
    }
}
