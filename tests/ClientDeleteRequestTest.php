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
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Model\DeleteOptions;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
#[CoversClass(Repository::class)]
class ClientDeleteRequestTest extends TestCase
{
    /**
     * @param array<int, mixed> $expectedSendArgs
     */
    private function createClient(array $expectedSendArgs): Client
    {
        $client = new Client(['master' => 'https://api.example.com']);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        $mockHttpMethodsClient
            ->expects($this->once())
            ->method('send')
            ->with(...$expectedSendArgs)
            ->willReturn(new Response(200, [], '{"kind":"Status","status":"Success"}'));

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        return $client;
    }

    public function testDeleteByNameSendsAnUppercaseDeleteWithoutBody(): void
    {
        $client = $this->createClient([
            'DELETE',
            'https://api.example.com/api/v1/namespaces/default/pods/foo',
            [],
            null,
        ]);

        $this->assertSame(
            ['kind' => 'Status', 'status' => 'Success'],
            $client->pods()->deleteByName('foo'),
        );
    }

    public function testDeleteWithOptionsSendsAJsonContentType(): void
    {
        $options = new DeleteOptions(['propagationPolicy' => 'Background']);

        $client = $this->createClient([
            'DELETE',
            'https://api.example.com/api/v1/namespaces/default/pods/foo',
            ['Content-Type' => 'application/json'],
            $options->getSchema(),
        ]);

        $this->assertSame(
            ['kind' => 'Status', 'status' => 'Success'],
            $client->pods()->delete(new Pod(['metadata' => ['name' => 'foo']]), $options),
        );
    }

    public function testDeleteAModelSendsAnUppercaseDelete(): void
    {
        $client = $this->createClient([
            'DELETE',
            'https://api.example.com/api/v1/namespaces/default/pods/foo',
            [],
            null,
        ]);

        $this->assertSame(
            ['kind' => 'Status', 'status' => 'Success'],
            $client->pods()->delete(new Pod(['metadata' => ['name' => 'foo']])),
        );
    }
}
