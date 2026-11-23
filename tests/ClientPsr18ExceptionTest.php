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

namespace Teknoo\Tests\Kubernetes;

use GuzzleHttp\Exception\ConnectException;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use SensitiveParameter;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\BadRequestException;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
#[CoversClass(BadRequestException::class)]
class ClientPsr18ExceptionTest extends TestCase
{
    /**
     * @return iterable<string, array{0: ClientExceptionInterface}>
     */
    public static function providePsr18Exceptions(): iterable
    {
        yield 'guzzle connection failure' => [
            new ConnectException('Connection refused', new Request('GET', 'https://api.example.com/api/v1/pods')),
        ];

        yield 'generic psr-18 client error' => [
            new class ('Network unreachable') extends RuntimeException implements ClientExceptionInterface {
            },
        ];
    }

    #[DataProvider('providePsr18Exceptions')]
    public function testAPsr18ClientErrorIsReportedAsABadRequest(
        #[SensitiveParameter]
        ClientExceptionInterface $exception,
    ): void {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($exception);

        $client = new Client(
            options: ['master' => 'https://api.example.com'],
            httpClient: $httpClient,
        );

        $thrown = null;
        try {
            $client->sendRequest(RequestMethod::Get, '/pods');
        } catch (BadRequestException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(BadRequestException::class, $thrown);
        $this->assertSame(500, $thrown->getCode());
        $this->assertSame($exception->getMessage(), $thrown->getMessage());
        $this->assertSame($exception, $thrown->getPrevious());
    }
}
