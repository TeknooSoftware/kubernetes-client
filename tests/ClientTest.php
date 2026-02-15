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

use BadMethodCallException;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException as HttpTransferException;
use Http\Discovery\Psr17FactoryDiscovery;
use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Collection\PodCollection;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exceptions\ApiServerException;
use Teknoo\Kubernetes\Exceptions\BadRequestException;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Repository\NamespaceRepository;
use Teknoo\Kubernetes\Repository\Repository;
use Teknoo\Kubernetes\RepositoryRegistry;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

use function json_encode;
use function method_exists;
use const PHP_EOL;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(BadRequestException::class)]
#[CoversClass(ApiServerException::class)]
#[CoversClass(Client::class)]
class ClientTest extends TestCase
{
    private const array JSON_BODY = [
        'items' => [
            [],
            [],
            [],
        ],
    ];

    private string $apiVersion = 'v1';

    private string $namespace = 'default';

    protected function tearDown(): void
    {
        Client::setTmpNameFunction(null);
        Client::setTmpDir(null);
        parent::tearDown();
    }

    public function testConstructorMissingMasterInConstruction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Client([
            'token' => 'foo',
            'namespace' => 'bar'
        ]);
    }

    public function testSetOptionsMissingMasterInConstruction(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->expectException(InvalidArgumentException::class);
        $client->setOptions(
            [
                'token' => 'foo2',
                'namespace' => 'bar2'
            ],
            true
        );
    }

    public function testSetOptions(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar',
        ]);

        $this->assertInstanceOf(Client::class, $client->setOptions(
            [
                'master' => 'https://api2.example.com',
                'token' => 'foo2',
                'namespace' => 'bar2'
            ],
            true
        ));
    }

    public function testSetOptionsWithTimeout(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar',
            'timeout' => 30,
        ]);

        $this->assertInstanceOf(Client::class, $client->setOptions(
            [
                'master' => 'https://api2.example.com',
                'token' => 'foo2',
                'namespace' => 'bar2',
                'timeout' => 30,
            ],
            true
        ));
    }

    public function testSetNamespace(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->assertInstanceOf(Client::class, $client->setNamespace(
            'bar2'
        ));
    }

    public function testSetPatchType(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->assertInstanceOf(Client::class, $client->setPatchType(PatchType::Json));

        $this->assertInstanceOf(Client::class, $client->setPatchType(PatchType::Strategic));

        $this->assertInstanceOf(Client::class, $client->setPatchType(PatchType::Merge));
    }

    public function testGetRepository(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->assertInstanceOf(NamespaceRepository::class, $client->namespaces());

        $this->assertInstanceOf(NamespaceRepository::class, $client->namespaces());
    }

    public function testGetWrongRepository(): void
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->expectException(BadMethodCallException::class);
        $client->foo();
    }

    public function testSendRequestWithAuthToken(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Get, 'poddy/');

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestWithAuthTokenWithEolAtMiddle(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo' . PHP_EOL . 'bar',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->never())
            ->method('sendRequest');

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $this->expectException(InvalidArgumentException::class);
        $client->sendRequest(RequestMethod::Get, 'poddy/');
    }

    public function testSendRequestWithCertificate(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'ca_cert' => 'barca',
            'client_cert' => 'foo',
            'client_key' => 'bar',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Get, 'poddy/');

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestToPatch(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Patch, 'poddy/', [], '{foo: "bar"}');

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestToPost(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Post, 'poddy/', [], ['foo' => "bar"]);

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestAuthViaFileToken(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => __DIR__ . '/fixtures/tokens/auth',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Get, 'poddy/', [], ['foo' => "bar"]);

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestAuthViaFileTokenWithEolAtEnd(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => __DIR__ . '/fixtures/tokens/auth_with_eol_at_end',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Get, 'poddy/', [], ['foo' => "bar"]);

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestAuthViaFileTokenWithEolInMiddle(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => __DIR__ . '/fixtures/tokens/auth_with_eol_in_middle',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->never())
            ->method('sendRequest');

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $this->expectException(InvalidArgumentException::class);
        $client->sendRequest(RequestMethod::Get, 'poddy/', [], ['foo' => "bar"]);
    }

    public function testSendRequestAuthViaUrl(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'http://foo.bar',
        ]);

        $client->setPatchType(PatchType::Json);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->never())
            ->method('sendRequest');

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $this->expectException(InvalidArgumentException::class);
        $client->sendRequest(RequestMethod::Get, 'poddy/', [], ['foo' => "bar"]);
    }

    public function testSendRequestJsonParsesResponse(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendRequest(RequestMethod::Get, 'poddy/');

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    public function testSendRequestWithHttpException(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willThrowException(new HttpTransferException());

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode(500);
        $client->sendRequest(RequestMethod::Get, 'poddy/');
    }

    public function testSendRequestWithHttpTransfertException(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(404, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willThrowException(new HttpException('foo', $this->createStub(RequestInterface::class), $response));

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionCode('404');
        $client->sendRequest(RequestMethod::Get, 'poddy/');
    }

    public function testSendStringableRequest(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendStringableRequest(RequestMethod::Get, 'poddy/', apiVersion: 'batch/v1');

        $this->assertEquals($jsonBody, $result);
    }

    public function testSendStreamableRequest(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $body = $this->createStub(StreamInterface::class);
        $response = new Response(200, [], $body);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->sendStreamableRequest(RequestMethod::Get, 'poddy/', ['foo' => 'bar']);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testHealth(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->health();

        $this->assertEquals(json_encode([
            'message' => 'Hello world',
        ]), $result);
    }

    public function testVersion(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $jsonBody = json_encode([
            'message' => 'Hello world',
        ]);

        $response = new Response(200, [], $jsonBody);

        $mockClientInterface->expects($this->once())
            ->method('sendRequest')
            ->withAnyParameters()
            ->willReturn($response);

        $httpClient = new HttpMethodsClient(
            $mockClientInterface,
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $httpClientProp->setValue($client, $httpClient);

        $result = $client->version();

        $this->assertEquals([
            'message' => 'Hello world',
        ], $result);
    }

    /**
     * Helper function for tests. Pass in a valid Client and have a fake response set on it.
     * @param array $mockResponseData Response body (will be JSON encoded)
     * @param array $expectedSendArgs Expected arguments of ->send() method
     * @param int $respStatusCode Response status code
     * @param array $respHeaders Response headers (key => value map)
     */
    private function setMockHttpResponse(
        Client $client,
        array  $mockResponseData,
        array  $expectedSendArgs,
        int    $respStatusCode = 200,
        array  $respHeaders = []
    ): void {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpMethodsClient');

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);

        $jsonBody = json_encode($mockResponseData, JSON_THROW_ON_ERROR);

        $response = new Response($respStatusCode, $respHeaders, $jsonBody);

        $mockHttpMethodsClient->expects($this->once())
            ->method('send')
            ->with(...$expectedSendArgs)
            ->willReturn($response);

        $httpClientProp->setValue($client, $mockHttpMethodsClient);
    }

    public function testGetPodsFromApi(): void
    {
        $client = new Client(['master' => 'https://kubernetes.io']);

        $this->setMockHttpResponse(
            $client,
            self::JSON_BODY,
            [
                'GET',
                'https://kubernetes.io/api/' . $this->apiVersion . '/namespaces/' . $this->namespace . '/pods'
            ]
        );

        $result = $client->pods()->find();

        $this->assertInstanceOf(PodCollection::class, $result);

        $this->assertCount(3, $result);

        $pod1 = $result->first();
        $this->assertInstanceOf(Pod::class, $pod1);
    }

    public static function providerForFailedResponses(): \Iterator
    {
        yield [
            500,
            ApiServerException::class,
            '/500 Error/',
        ];
        yield [
            401,
            ApiServerException::class,
            '/Authentication Exception/',
        ];
        yield [
            403,
            ApiServerException::class,
            '/Authentication Exception/',
        ];
        yield [
            404,
            ApiServerException::class,
            '/Error hath occurred/',
        ];
    }

    #[DataProvider('providerForFailedResponses')]
    public function testExceptionIsThrownOnFailureResponse(int $respCode, string $exceptionClass, string $msgRegEx): void
    {
        $client = new Client(['master' => 'https://kubernetes.io']);

        $this->setMockHttpResponse(
            $client,
            ['message' => 'Error hath occurred'],
            ["GET", "https://kubernetes.io/api/v1/namespaces/default/api/anything", [], null],
            $respCode
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessageMatches($msgRegEx);

        $client->sendRequest(RequestMethod::Get, '/api/anything');
    }

    public function testLoadFromKubeConfigFile(): void
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file): string => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        $this->assertEquals(new Client(
            options: [
                'master' => 'https://your-k8s-cluster.com',
                'ca_cert' => '/tmp/kubernetes-client-ca-cert.pem',
                'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                'client_key' => '/tmp/kubernetes-client-client-key.pem',
            ],
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ), Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ));
    }

    public function testLoadFromKubeConfigFileWithoutServerCertificate(): void
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file): string => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        $this->assertEquals(new Client(
            options: [
                'master' => 'https://your-k8s-cluster.com',
                'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                'client_key' => '/tmp/kubernetes-client-client-key.pem',
                'verify' => false,
            ],
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ), Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_server_certificate.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ));
    }

    public function testLoadFromKubeConfigFileWithUnsecureAPI(): void
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file): string => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        $this->assertEquals(new Client(
            options: [
                'master' => 'http://your-k8s-cluster.com',
                'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                'client_key' => '/tmp/kubernetes-client-client-key.pem',
                'verify' => true,
            ],
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ), Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.unsecure.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        ));
    }

    public function testLoadFromKubeConfigFileWithoutServer(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_server.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithUserInContextUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.user_in_context_undefined.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithClusterInContextUndefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.cluster_in_context_undefined.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutCluster(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_cluster.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_context.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutCurrentContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_current_context.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithInvalidCurrentContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.with_invalid_current_context.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutUserInContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_user_in_context.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutClusterInContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_cluster_in_context.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutContextInContext(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_current_context_in_contexts.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutUsers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_users.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithWrongFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.missing.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigArrayWithWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('', FileFormat::Array);
    }

    public function testLoadFromKubeConfigJsonWithWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig([], FileFormat::Json);
    }

    public function testLoadFromKubeConfigJsonWithMalFormedString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('aa', FileFormat::Json);
    }

    public function testLoadFromKubeConfigYamlWithWrongType(): void
    {

        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig([], FileFormat::Yaml);
    }

    public function testLoadFromKubeConfigYamlWithMalFormedString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('@', FileFormat::Yaml);
    }

    public function testSetTmpDir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Client::setTmpDir('foo/bar');
    }
}
