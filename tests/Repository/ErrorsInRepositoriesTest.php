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

namespace Teknoo\Tests\Kubernetes\Repository;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Repository\Repository;


/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(Repository::class)]
class ErrorsInRepositoriesTest extends PHPUnitTestCase
{
    private ?Client $client = null;

    protected function getClientMock(bool $returnEmpty = false, bool $missingItems = false,): Stub&Client
    {
        if (null === $this->client) {
            $this->client = $this->createStub(Client::class);

            $result = [
                'items' => [
                    ['metadata' => ['name' => 'foo']]
                ]
            ];

            if ($returnEmpty) {
                $result = ['items' => []];
            }

            if ($missingItems) {
                $result = [];
            }

            $this->client
                ->method('sendRequest')
                ->willReturn($result);

            $stream = $this->createStub(StreamInterface::class);
            $response = $this->createStub(ResponseInterface::class);

            $response
                ->method('getBody')
                ->willReturn($stream);

            $this->client
                ->method('sendStreamableRequest')
                ->willReturn($response);

            $this->client
                ->method('sendStringableRequest')
                ->willReturn('foo');
        }

        return $this->client;
    }

    public function testGetItemsWithoutCollectionName(): void
    {
        $this->expectException(LogicException::class);
        $collection = new class ($this->getClientMock()) extends Repository {
            protected static ?string $collectionClassName = null;
        };
        $collection->find([]);
    }

    public function testGetItemsWithWrongCollectionName(): void
    {
        $this->expectException(LogicException::class);
        $collection = new class ($this->getClientMock()) extends Repository {
            protected static ?string $collectionClassName = \stdClass::class;
        };
        $collection->find([]);
    }
}
