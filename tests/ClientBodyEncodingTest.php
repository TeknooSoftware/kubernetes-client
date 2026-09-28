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
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
class ClientBodyEncodingTest extends TestCase
{
    /**
     * @return iterable<string, array{0: array<int|string, mixed>, 1: string}>
     */
    public static function provideBodies(): iterable
    {
        yield 'lists stay lists' => [['a' => ['x', 'y'], 'e' => []], '{"a":["x","y"],"e":{}}'];
        yield 'empty body is an object' => [[], '{}'];
        yield 'json patch document' => [[['op' => 'add', 'path' => '/a', 'value' => 1]], '[{"op":"add","path":"\/a","value":1}]'];
        yield 'nested empty map' => [['metadata' => ['labels' => []]], '{"metadata":{"labels":{}}}'];
    }

    /**
     * @param array<int|string, mixed> $body
     */
    #[DataProvider('provideBodies')]
    public function testArrayBodiesAreEncodedWithoutForcingObjects(array $body, string $expected): void
    {
        $client = new Client(['master' => 'https://api.example.com']);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        $mockHttpMethodsClient
            ->expects($this->once())
            ->method('send')
            ->with(
                'POST',
                'https://api.example.com/api/v1/namespaces/default/pods',
                ['Content-Type' => 'application/json'],
                $expected,
            )
            ->willReturn(new Response(200, [], '{"kind":"Pod"}'));

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        $this->assertSame(['kind' => 'Pod'], $client->sendRequest(RequestMethod::Post, '/pods', body: $body));
    }
}
