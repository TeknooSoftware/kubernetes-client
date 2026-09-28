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
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Certificate;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\CertificateRepository;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\Repository\Strategy\PatchMergeTrait;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

use function array_column;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
#[CoversClass(Repository::class)]
#[CoversClass(CertificateRepository::class)]
#[CoversTrait(PatchMergeTrait::class)]
class ClientPatchTypeTest extends TestCase
{
    /**
     * @var array<int, array{method: string, uri: string, headers: array<string, string>, body: mixed}>
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
                function (string $method, mixed $uri, array $headers, mixed $body): Response {
                    $this->requests[] = [
                        'method' => $method,
                        'uri' => (string) $uri,
                        'headers' => $headers,
                        'body' => $body,
                    ];

                    return new Response(200, [], '{"kind":"Pod","metadata":{"name":"foo"}}');
                }
            );

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        return $client;
    }

    /**
     * @return array<int, string>
     */
    private function sentContentTypes(): array
    {
        return array_column(array_column($this->requests, 'headers'), 'Content-Type');
    }

    public function testAPatchTypeGivenToARequestDoesNotBecomeTheDefault(): void
    {
        $client = $this->createClient(3);

        $client->sendRequest(RequestMethod::Patch, '/pods/foo', [], '{}', patchType: PatchType::Merge);
        $client->sendRequest(RequestMethod::Patch, '/pods/foo', [], '{}');
        $client->sendStringableRequest(RequestMethod::Patch, '/pods/foo', [], '{}', patchType: PatchType::Json);

        $this->assertSame(
            [
                'application/merge-patch+json',
                'application/strategic-merge-patch+json',
                'application/json-patch+json',
            ],
            $this->sentContentTypes(),
        );
    }

    public function testAJsonPatchDoesNotLeakIntoTheNextPatch(): void
    {
        $client = $this->createClient(2);
        $pod = new Pod(['metadata' => ['name' => 'foo']]);

        $client->pods()->applyJsonPatch($pod, [['op' => 'replace', 'path' => '/spec/foo', 'value' => 'baz']]);
        $client->pods()->patch($pod);

        $this->assertSame(
            ['application/json-patch+json', 'application/strategic-merge-patch+json'],
            $this->sentContentTypes(),
        );
        $this->assertSame('[{"op":"replace","path":"\/spec\/foo","value":"baz"}]', $this->requests[0]['body']);
        $this->assertSame($pod->getSchema(), $this->requests[1]['body']);
    }

    public function testAMergePatchRepositoryDoesNotLeakIntoOtherRepositories(): void
    {
        $client = $this->createClient(3);
        $pod = new Pod(['metadata' => ['name' => 'foo']]);
        $certificate = new Certificate(['metadata' => ['name' => 'cert']]);

        $client->certificates()->patch($certificate);
        $client->pods()->patch($pod);
        $client->certificates()->patch($certificate);

        $this->assertSame(
            [
                'application/merge-patch+json',
                'application/strategic-merge-patch+json',
                'application/merge-patch+json',
            ],
            $this->sentContentTypes(),
        );
    }

    public function testTheDefaultPatchTypeOfTheClientIsHonouredAndNotResetByRepositories(): void
    {
        $client = $this->createClient(4);
        $pod = new Pod(['metadata' => ['name' => 'foo']]);
        $certificate = new Certificate(['metadata' => ['name' => 'cert']]);

        $client->setPatchType(PatchType::Json);
        $client->pods()->patch($pod);
        $client->certificates()->patch($certificate);
        $client->pods()->patch($pod);
        $client->setPatchType();
        $client->pods()->patch($pod);

        $this->assertSame(
            [
                'application/json-patch+json',
                'application/merge-patch+json',
                'application/json-patch+json',
                'application/strategic-merge-patch+json',
            ],
            $this->sentContentTypes(),
        );
    }

    public function testNonPatchRequestsIgnoreThePatchType(): void
    {
        $client = $this->createClient(2);

        $client->sendRequest(RequestMethod::Get, '/pods/foo', patchType: PatchType::Merge);
        $client->sendRequest(RequestMethod::Post, '/pods', body: '{}', patchType: PatchType::Merge);

        $this->assertArrayNotHasKey('Content-Type', $this->requests[0]['headers']);
        $this->assertSame('application/json', $this->requests[1]['headers']['Content-Type']);
    }
}
