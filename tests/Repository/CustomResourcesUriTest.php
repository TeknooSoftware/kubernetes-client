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

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Model\Issuer;
use Teknoo\Kubernetes\Model\SubnamespaceAnchor;
use Teknoo\Kubernetes\Repository\IssuerRepository;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\Repository\Strategy\PatchMergeTrait;
use Teknoo\Kubernetes\Repository\SubnamespaceAnchorRepository;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Repository::class)]
#[CoversClass(IssuerRepository::class)]
#[CoversClass(SubnamespaceAnchorRepository::class)]
#[CoversTrait(PatchMergeTrait::class)]
class CustomResourcesUriTest extends TestCase
{
    /**
     * @var array<int, array{method: string, uri: string, headers: array<string, string>}>
     */
    private array $requests = [];

    private function createClient(int $expectedRequests): Client
    {
        $this->requests = [];
        $client = new Client(['master' => 'https://api.example.com']);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        $mockHttpMethodsClient
            ->expects($this->exactly($expectedRequests))
            ->method('send')
            ->willReturnCallback(
                function (string $method, mixed $uri, array $headers): Response {
                    $this->requests[] = ['method' => $method, 'uri' => (string) $uri, 'headers' => $headers];

                    return new Response(200, [], '{"items":[]}');
                }
            );

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        return $client;
    }

    public function testIssuersUseTheCertManagerApiGroup(): void
    {
        $client = $this->createClient(2);

        $client->issuers()->find();
        $client->issuers()->patch(new Issuer(['metadata' => ['name' => 'my-issuer']]));

        $this->assertSame(
            'https://api.example.com/apis/cert-manager.io/v1/namespaces/default/issuers',
            $this->requests[0]['uri'],
        );
        $this->assertSame(
            'https://api.example.com/apis/cert-manager.io/v1/namespaces/default/issuers/my-issuer',
            $this->requests[1]['uri'],
        );
        $this->assertSame('application/merge-patch+json', $this->requests[1]['headers']['Content-Type']);
    }

    public function testSubnamespaceAnchorsUseTheirPluralAndAMergePatch(): void
    {
        $client = $this->createClient(2);

        $client->subnamespacesAnchors()->find();
        $client->subnamespacesAnchors()->patch(new SubnamespaceAnchor(['metadata' => ['name' => 'child']]));

        $this->assertSame(
            'https://api.example.com/apis/hnc.x-k8s.io/v1/namespaces/default/subnamespaceanchors',
            $this->requests[0]['uri'],
        );
        $this->assertSame(
            'https://api.example.com/apis/hnc.x-k8s.io/v1/namespaces/default/subnamespaceanchors/child',
            $this->requests[1]['uri'],
        );
        $this->assertSame('PATCH', $this->requests[1]['method']);
        $this->assertSame('application/merge-patch+json', $this->requests[1]['headers']['Content-Type']);
    }
}
