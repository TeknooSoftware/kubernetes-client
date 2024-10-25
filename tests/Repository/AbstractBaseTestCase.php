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

namespace Teknoo\Tests\Kubernetes\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Contracts\Repository\StreamingParser;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Exceptions\TimeExceededAboutContinueException;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
abstract class AbstractBaseTestCase extends PHPUnitTestCase
{
    private ?Client $client = null;

    abstract protected function getRepository(): Repository;

    abstract protected function getCollectionClassName(): string;

    abstract protected function getModel(): Model;

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

            $this->client->expects($this->any())
                ->method('sendRequest')
                ->willReturn($result);

            $stream = $this->createMock(StreamInterface::class);
            $response = $this->createMock(ResponseInterface::class);

            $response->expects($this->any())
                ->method('getBody')
                ->willReturn($stream);

            $this->client->expects($this->any())
                ->method('sendStreamableRequest')
                ->willReturn($response);

            $this->client->expects($this->any())
                ->method('sendStringableRequest')
                ->willReturn('foo');
        }

        return $this->client;
    }

    public function testCreate(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->create($this->getModel())
        );
    }

    public function testUpdate(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->update($this->getModel())
        );
    }

    public function testPatch(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->patch($this->getModel())
        );
    }

    public function testApplyJsonPatch(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->applyJsonPatch($this->getModel(), ['spec' => ['foo' => 'bar']])
        );
    }

    public function testApplyForNonExisting(): void
    {
        $this->getClientMock(true);
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->apply($this->getModel())
        );
    }

    public function testApplyForExisting(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->apply($this->getModel())
        );
    }

    public function testDelete(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->delete($this->getModel())
        );
    }

    public function testDeleteByName(): void
    {
        $repository = $this->getRepository();
        self::assertIsArray(
            $repository->deleteByName('foo')
        );
    }

    public function testSetLabelSelector(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $repository::class,
            $repository->setLabelSelector(
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            )
        );
    }

    public function testSetFieldSelector(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $repository::class,
            $repository->setFieldSelector(
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            )
        );
    }

    public function testFind(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->find(
                ['foo' => 'bar'],
            )
        );

        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->setFieldSelector(['foo' => 'bar'], ['bar' => 'foo'])
                ->setLabelSelector(['fool' => 'bar', 'world' => null], ['barl' => 'foo'])
                ->find(['foo' => 'bar'])
        );
    }

    public function testFindWithLimit(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->find(
                ['foo' => 'bar'],
                10,
            )
        );

        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->setFieldSelector(['foo' => 'bar'], ['bar' => 'foo'])
                ->setLabelSelector(['fool' => 'bar', 'world' => null], ['barl' => 'foo'])
                ->find(['foo' => 'bar'], 10)
        );
    }

    public function testContinue(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->continue(
                ['foo' => 'bar'],
                'foo',
            )
        );

        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getCollectionClassName(),
            $repository->continue(['foo' => 'bar'], 'foo')
        );
    }

    public function testContinueTimeExceeded(): void
    {
        $repository = $this->getRepository();

        $this->getClientMock()
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new ApiServerException('error', 410)
            );

        $this->expectException(TimeExceededAboutContinueException::class);
        $repository->continue(
            ['foo' => 'bar'],
            'foo',
        );
    }

    public function testContinueOtherException(): void
    {
        $repository = $this->getRepository();

        $this->getClientMock()
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new ApiServerException('error', 400)
            );

        $this->expectException(ApiServerException::class);
        $repository->continue(
            ['foo' => 'bar'],
            'foo',
        );
    }

    public function testFindMissingItems(): void
    {
        $this->getClientMock(missingItems: true);
        $repository = $this->getRepository();
        $this->expectException(RuntimeException::class);
        $repository->find(
            ['foo' => 'bar'],
        );
    }

    public function testFirst(): void
    {
        $repository = $this->getRepository();
        self::assertInstanceOf(
            $this->getModel()::class,
            $repository->first()
        );
    }

    public function testStream(): void
    {
        $streamer = $this->createMock(StreamingParser::class);
        $streamer->expects($this->once())->method('parse');

        $repository = $this->getRepository();
        self::assertInstanceOf(
            $repository::class,
            $repository->stream(
                $this->getModel(),
                $streamer,
            )
        );
    }

    public function testExists(): void
    {
        $repository = $this->getRepository();
        self::assertIsBool(
            $repository->exists(
                'foo',
            )
        );
    }
}
