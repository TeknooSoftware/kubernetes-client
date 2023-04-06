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

namespace Teknoo\Tests\Kubernetes\Repository;

use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Repository\Repository;


/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\Repository\Repository
 */
class ErrorsInRepositoriesTest extends PHPUnitTestCase
{
    private ?Client $client = null;

    protected function getClientMock(bool $returnEmpty = false, bool $missingItems = false,): MockObject&Client
    {
        if (null === $this->client) {
            $this->client = $this->createMock(Client::class);

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

            $this->client->expects(self::any())
                ->method('sendRequest')
                ->willReturn($result);

            $stream = $this->createMock(StreamInterface::class);
            $response = $this->createMock(ResponseInterface::class);

            $response->expects(self::any())
                ->method('getBody')
                ->willReturn($stream);

            $this->client->expects(self::any())
                ->method('sendStreamableRequest')
                ->willReturn($response);

            $this->client->expects(self::any())
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
