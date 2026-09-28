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
use Teknoo\Kubernetes\Model\PersistentVolume;
use Teknoo\Kubernetes\Repository\PersistentVolumeRepository;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Repository::class)]
#[CoversClass(PersistentVolumeRepository::class)]
class PersistentVolumeStreamTest extends TestCase
{
    public function testWatchingAPersistentVolumeUsesTheClusterScopedPath(): void
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
                '/persistentvolumes',
                ['watch' => '1', 'timeoutSeconds' => '30', 'fieldSelector' => 'metadata.name=pv-1'],
                null,
                false,
                null,
            )
            ->willReturn($response);

        $parser = $this->createMock(StreamingParser::class);
        $parser->expects($this->once())->method('parse')->with($stream);

        $repository = new PersistentVolumeRepository($client);
        $repository->stream(new PersistentVolume(['metadata' => ['name' => 'pv-1']]), $parser);
    }

    public function testListingPersistentVolumesUsesTheClusterScopedPath(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(RequestMethod::Get, '/persistentvolumes', [], null, false, null)
            ->willReturn(['items' => []]);

        $repository = new PersistentVolumeRepository($client);
        $this->assertCount(0, $repository->find());
    }
}
