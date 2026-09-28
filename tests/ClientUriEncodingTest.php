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
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Node;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\NodeRepository;
use Teknoo\Kubernetes\Repository\PodRepository;
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
#[CoversClass(PodRepository::class)]
#[CoversClass(NodeRepository::class)]
class ClientUriEncodingTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $uris = [];

    /**
     * @param array<string, string> $options
     */
    private function createClient(array $options, int $expectedRequests = 1): Client
    {
        $this->uris = [];
        $client = new Client($options);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        $mockHttpMethodsClient
            ->expects($this->exactly($expectedRequests))
            ->method('send')
            ->willReturnCallback(
                function (string $method, mixed $uri): Response {
                    $this->uris[] = (string) $uri;

                    return new Response(200, [], '{"kind":"Status","items":[]}');
                }
            );

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        return $client;
    }

    public function testTheNamespaceAndTheNameAreEncodedAsSinglePathSegments(): void
    {
        $client = $this->createClient(['master' => 'https://api.example.com', 'namespace' => 'a b']);

        $client->pods()->deleteByName('my pod/../x?dryRun=All');

        $this->assertSame(
            ['https://api.example.com/api/v1/namespaces/a%20b/pods/my%20pod%2F..%2Fx%3FdryRun%3DAll'],
            $this->uris,
        );
    }

    public function testValidNamesAreUnchanged(): void
    {
        $client = $this->createClient(['master' => 'https://api.example.com'], 3);
        $pod = new Pod(['metadata' => ['name' => 'my-pod.v1']]);

        $client->setNamespace('kube-system');
        $client->pods()->update($pod);
        $client->pods()->patch($pod);
        $client->pods()->delete($pod);

        $this->assertSame(
            [
                'https://api.example.com/api/v1/namespaces/kube-system/pods/my-pod.v1',
                'https://api.example.com/api/v1/namespaces/kube-system/pods/my-pod.v1',
                'https://api.example.com/api/v1/namespaces/kube-system/pods/my-pod.v1',
            ],
            $this->uris,
        );
    }

    public function testSubResourcesUrisAreEncoded(): void
    {
        $client = $this->createClient(['master' => 'https://api.example.com'], 3);

        $client->pods()->logs(new Pod(['metadata' => ['name' => 'p 1']]));
        $client->pods()->exec(new Pod(['metadata' => ['name' => 'p/2']]));
        $client->nodes()->proxy(new Node(['metadata' => ['name' => 'node/1']]), RequestMethod::Get, 'metrics');

        $this->assertSame(
            [
                'https://api.example.com/api/v1/namespaces/default/pods/p%201/log',
                'https://api.example.com/api/v1/namespaces/default/pods/p%2F2/exec',
                'https://api.example.com/api/v1/nodes/node%2F1/proxy/metrics',
            ],
            $this->uris,
        );
    }

    public function testATrailingSlashOnTheMasterUrlIsIgnored(): void
    {
        $client = $this->createClient(['master' => 'https://api.example.com/'], 2);

        $client->pods()->find();
        $client->health();

        $this->assertSame(
            [
                'https://api.example.com/api/v1/namespaces/default/pods',
                'https://api.example.com/healthz',
            ],
            $this->uris,
        );
    }
}
