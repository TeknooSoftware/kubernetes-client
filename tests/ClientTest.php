<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Tests\Kubernetes;

use BadMethodCallException;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException as HttpTransferException;
use Http\Client\HttpClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\StreamFactoryDiscovery;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
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
use Teknoo\Kubernetes\RepositoryRegistry;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

use function json_encode;
use function method_exists;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\Client
 * @covers      \Teknoo\Kubernetes\Exceptions\ApiServerException
 * @covers      \Teknoo\Kubernetes\Exceptions\BadRequestException
 * @covers      \Teknoo\Kubernetes\Enums\FileFormat
 * @covers      \Teknoo\Kubernetes\Enums\PatchType
 * @covers      \Teknoo\Kubernetes\Enums\RequestMethod
 */
class ClientTest extends TestCase
{
    private const JSON_BODY = [
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

    public function testConstructorMissingMasterInConstruction()
    {
        $this->expectException(InvalidArgumentException::class);
        $client = new Client([
            'token' => 'foo',
            'namespace' => 'bar'
        ]);
    }

    public function testSetOptionsMissingMasterInConstruction()
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

    public function testSetOptions()
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        self::assertInstanceOf(
            Client::class,
            $client->setOptions(
                [
                    'master' => 'https://api2.example.com',
                    'token' => 'foo2',
                    'namespace' => 'bar2'
                ],
                true
            )
        );
    }

    public function testSetNamespace()
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        self::assertInstanceOf(
            Client::class,
            $client->setNamespace(
                'bar2'
            )
        );
    }

    public function testSetPatchType()
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        self::assertInstanceOf(
            Client::class,
            $client->setPatchType(PatchType::Json)
        );

        self::assertInstanceOf(
            Client::class,
            $client->setPatchType(PatchType::Strategic)
        );

        self::assertInstanceOf(
            Client::class,
            $client->setPatchType(PatchType::Merge)
        );
    }

    public function testGetRepository()
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        self::assertInstanceOf(
            NamespaceRepository::class,
            $client->namespaces()
        );

        self::assertInstanceOf(
            NamespaceRepository::class,
            $client->namespaces()
        );
    }

    public function testGetWrongRepository()
    {
        $client = new Client([
            'master' => 'https://api.example.com',
            'token' => 'foo',
            'namespace' => 'bar'
        ]);

        $this->expectException(BadMethodCallException::class);
        $client->foo();
    }

    public function testGetInvalidRepository()
    {
        $registry = $this->createMock(RepositoryRegistry::class);
        $registry->expects(self::any())
            ->method('offsetGet')
            ->willReturn(stdClass::class);

        $registry->expects(self::any())
            ->method('offsetExists')
            ->willReturn(true);

        $client = new Client(
            options: [
                'master' => 'https://api.example.com',
                'token' => 'foo',
                'namespace' => 'bar'
            ],
            repositoryRegistry: $registry,
        );

        $this->expectException(BadMethodCallException::class);
        $client->foo();
    }

    public function testSendRequestWithAuthToken(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestWithCertificate(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestToPatch(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestToPost(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestAuthVIaFileToken(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestJsonParsesResponse(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
    }

    public function testSendRequestWithHttpException(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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
        $httpClientProp->setAccessible(true);

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
            ->willThrowException(new HttpException('foo', $this->createMock(RequestInterface::class), $response));

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
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            $jsonBody,
            $result
        );
    }

    public function testSendStreamableRequest(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

        $client = new Client([
            'master' => 'https://api.example.com',
        ]);

        $mockClientInterface = $this->createMock(ClientInterface::class);

        $body = $this->createMock(StreamInterface::class);
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

        self::assertInstanceOf(
            ResponseInterface::class,
            $result
        );
    }

    public function testHealth(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            json_encode([
                'message' => 'Hello world',
            ]),
            $result
        );
    }

    public function testVersion(): void
    {
        $httpClientProp = new ReflectionProperty(Client::class, 'httpClient');
        $httpClientProp->setAccessible(true);

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

        self::assertEquals(
            [
                'message' => 'Hello world',
            ],
            $result
        );
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
        $httpClientProp->setAccessible(true);

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

        self::assertInstanceOf(PodCollection::class, $result);

        self::assertCount(3, $result);

        $pod1 = $result->first();
        self::assertInstanceOf(Pod::class, $pod1);
    }

    public static function providerForFailedResponses(): array
    {
        return [
            [
                500,
                ApiServerException::class,
                '/500 Error/',
            ],
            [
                401,
                ApiServerException::class,
                '/Authentication Exception/',
            ],
            [
                403,
                ApiServerException::class,
                '/Authentication Exception/',
            ],
            [
                404,
                ApiServerException::class,
                '/Error hath occurred/',
            ],
        ];
    }

    /**
     * @dataProvider providerForFailedResponses
     */
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
        if (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp($msgRegEx);
        } else {
            $this->expectExceptionMessageMatches($msgRegEx);
        }

        $client->sendRequest(RequestMethod::Get, '/api/anything');
    }

    public function testLoadFromKubeConfigFile()
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file) => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        self::assertEquals(
            new Client(
                options: [
                    'master' => 'https://your-k8s-cluster.com',
                    'ca_cert' => '/tmp/kubernetes-client-ca-cert.pem',
                    'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                    'client_key' => '/tmp/kubernetes-client-client-key.pem',
                ],
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            ),
            Client::loadFromKubeConfigFile(
                filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            )
        );
    }

    public function testLoadFromKubeConfigFileWithoutServerCertificate()
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file) => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        self::assertEquals(
            new Client(
                options: [
                    'master' => 'https://your-k8s-cluster.com',
                    'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                    'client_key' => '/tmp/kubernetes-client-client-key.pem',
                    'verify' => false,
                ],
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            ),
            Client::loadFromKubeConfigFile(
                filePath: __DIR__ . '/fixtures/config/kubeconfig.without_server_certificate.example',
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            )
        );
    }

    public function testLoadFromKubeConfigFileWithUnsecureAPI()
    {
        Client::setTmpNameFunction(
            fn (string $dir, string $file) => $dir . '/' . $file
        );
        Client::setTmpDir('/tmp');
        self::assertEquals(
            new Client(
                options: [
                    'master' => 'http://your-k8s-cluster.com',
                    'client_cert' => '/tmp/kubernetes-client-client-cert.pem',
                    'client_key' => '/tmp/kubernetes-client-client-key.pem',
                    'verify' => true,
                ],
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            ),
            Client::loadFromKubeConfigFile(
                filePath: __DIR__ . '/fixtures/config/kubeconfig.unsecure.example',
                httpClient: $this->createMock(HttpClient::class),
                httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
            )
        );
    }

    public function testLoadFromKubeConfigFileWithoutServer()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_server.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithUserInContextUndefined()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.user_in_context_undefined.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithClusterInContextUndefined()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.cluster_in_context_undefined.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutCluster()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_cluster.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutContext()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_context.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutCurrentContext()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_current_context.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutUserInContext()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_user_in_context.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutClusterInContext()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_cluster_in_context.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutContextInContext()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_current_context_in_contexts.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithoutUsers()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.without_users.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigFileWithWrongFile()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.missing.example',
            httpClient: $this->createMock(HttpClient::class),
            httpStreamFactory: $this->createMock(StreamFactoryInterface::class),
        );
    }

    public function testLoadFromKubeConfigArrayWithWrongType()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('', FileFormat::Array);
    }

    public function testLoadFromKubeConfigJsonWithWrongType()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig([], FileFormat::Json);
    }

    public function testLoadFromKubeConfigJsonWithMalFormedString()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('aa', FileFormat::Json);
    }

    public function testLoadFromKubeConfigYamlWithWrongType()
    {

        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig([], FileFormat::Yaml);
    }

    public function testLoadFromKubeConfigYamlWithMalFormedString()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::loadFromKubeConfig('@', FileFormat::Yaml);
    }

    public function testSetTmpDir()
    {
        $this->expectException(InvalidArgumentException::class);
        Client::setTmpDir('foo/bar');
    }
}
