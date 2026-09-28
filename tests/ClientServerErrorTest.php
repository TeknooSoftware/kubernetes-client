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

namespace Teknoo\Tests\Kubernetes;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
#[CoversClass(ApiServerException::class)]
class ClientServerErrorTest extends TestCase
{
    /**
     * @return iterable<string, array{0: int}>
     */
    public static function provideServerErrors(): iterable
    {
        yield '500' => [500];
        yield '502' => [502];
        yield '503' => [503];
        yield '504' => [504];
    }

    #[DataProvider('provideServerErrors')]
    public function testTheRealStatusCodeIsReported(int $status): void
    {
        $client = new Client(['master' => 'https://api.example.com']);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        $mockHttpMethodsClient
            ->expects($this->once())
            ->method('send')
            ->willReturn(new Response($status, [], '{"message":"upstream is down"}'));

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        $thrown = null;
        try {
            $client->sendRequest(RequestMethod::Get, '/pods');
        } catch (ApiServerException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(ApiServerException::class, $thrown);
        $this->assertSame($status, $thrown->getCode());
        $this->assertStringContainsString("Server responded with $status Error", $thrown->getMessage());
        $this->assertStringContainsString('upstream is down', $thrown->getMessage());
    }
}
