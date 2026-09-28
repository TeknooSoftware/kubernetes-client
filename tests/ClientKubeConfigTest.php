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

use Http\Discovery\Psr17FactoryDiscovery;
use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Enums\RequestMethod;

use function bin2hex;
use function file_get_contents;
use function glob;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;
use function unlink;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
class ClientKubeConfigTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/kc-config-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700);
        Client::setTmpDir($this->directory);
    }

    protected function tearDown(): void
    {
        Client::setTmpDir(null);
        Client::setTmpNameFunction(null);

        $files = glob($this->directory . '/*');

        if (false === $files) {

            $files = [];

        }


        foreach ($files as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
        parent::tearDown();
    }

    /**
     * @return array<int, string>
     */
    private function listTemporaryFiles(): array
    {
        $files = glob($this->directory . '/kubernetes-client-*');
        if (false === $files) {
            $files = [];
        }

        return $files;
    }

    public function testInvalidBase64CertificateDataIsRefused(): void
    {
        $thrown = null;
        try {
            Client::loadFromKubeConfigFile(
                filePath: __DIR__ . '/fixtures/config/kubeconfig.invalid_base64.example',
                httpClient: $this->createStub(ClientInterface::class),
                httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
            );
        } catch (InvalidArgumentException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $thrown);
        $this->assertStringContainsString('certificate-authority-data', $thrown->getMessage());
        $this->assertStringContainsString('base64', $thrown->getMessage());
        $this->assertSame([], $this->listTemporaryFiles(), 'No empty certificate file must be written');
    }

    public function testInvalidBase64KeyDataRemovesTheFilesAlreadyWritten(): void
    {
        $thrown = null;
        try {
            Client::loadFromKubeConfig(
                content: [
                    'clusters' => [
                        [
                            'name' => 'cluster',
                            'cluster' => [
                                'server' => 'https://api.example.com',
                                'certificate-authority-data' => 'Zm9vLWRhdGE=',
                            ],
                        ],
                    ],
                    'contexts' => [
                        ['name' => 'ctx', 'context' => ['cluster' => 'cluster', 'user' => 'user']],
                    ],
                    'current-context' => 'ctx',
                    'users' => [
                        [
                            'name' => 'user',
                            'user' => [
                                'client-certificate-data' => 'Zm9vLWRhdGE=',
                                'client-key-data' => '%%%',
                            ],
                        ],
                    ],
                ],
                format: FileFormat::Array,
                httpClient: $this->createStub(ClientInterface::class),
                httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
            );
        } catch (InvalidArgumentException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $thrown);
        $this->assertStringContainsString('client-key-data', $thrown->getMessage());
        $this->assertSame([], $this->listTemporaryFiles(), 'The CA and certificate files must be removed');
    }

    public function testNonStringBase64DataIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/certificate-authority-data/');

        Client::loadFromKubeConfig(
            content: [
                'clusters' => [
                    [
                        'name' => 'cluster',
                        'cluster' => [
                            'server' => 'https://api.example.com',
                            'certificate-authority-data' => ['not' => 'a string'],
                        ],
                    ],
                ],
                'contexts' => [
                    ['name' => 'ctx', 'context' => ['cluster' => 'cluster', 'user' => 'user']],
                ],
                'current-context' => 'ctx',
                'users' => [
                    ['name' => 'user', 'user' => ['token' => 'foo']],
                ],
            ],
            format: FileFormat::Array,
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    private function readOption(Client $client, string $property): mixed
    {
        return new ReflectionProperty(Client::class, $property)->getValue($client);
    }

    public function testCertificatePathsAndNamespaceAreReadFromTheKubeConfigFile(): void
    {
        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.with_paths.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );

        $directory = __DIR__ . '/fixtures/config';
        $this->assertSame($directory . '/certs/ca.pem', $this->readOption($client, 'caCertificate'));
        $this->assertSame($directory . '/certs/client.pem', $this->readOption($client, 'clientCertificate'));
        $this->assertSame($directory . '/certs/client-key.pem', $this->readOption($client, 'clientKey'));
        $this->assertSame('team-a', $this->readOption($client, 'namespace'));
        $this->assertNull($this->readOption($client, 'token'));
        $this->assertTrue($this->readOption($client, 'verify'));
        $this->assertSame([], $this->listTemporaryFiles(), 'Files given by path are used as is');
    }

    public function testTokenFileAndInsecureFlagAreReadFromTheKubeConfigFile(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(
                static fn (RequestInterface $request): bool => 'Bearer file-token' === $request->getHeaderLine('Authorization')
            ))
            ->willReturn(new Response(200, [], '{"items":[]}'));

        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.token_file.example',
            httpClient: $httpClient,
            httpRequestFactory: Psr17FactoryDiscovery::findRequestFactory(),
            httpStreamFactory: Psr17FactoryDiscovery::findStreamFactory(),
        );

        $this->assertSame(__DIR__ . '/fixtures/config/token.txt', $this->readOption($client, 'token'));
        $this->assertFalse($this->readOption($client, 'verify'));
        $this->assertSame(['items' => []], $client->sendRequest(RequestMethod::Get, '/pods'));
    }

    public function testAMissingCertificateFileIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('#certs/missing\.pem.*certificate-authority#');

        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.missing_ca_file.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testEmbeddedDataTakesPrecedenceOverPaths(): void
    {
        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.data_precedence.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );

        $this->assertCount(3, $this->listTemporaryFiles());
        $this->assertStringStartsWith($this->directory . '/', (string) $this->readOption($client, 'caCertificate'));
        $this->assertSame('foobar', $this->readOption($client, 'token'));
    }

    public function testRelativePathsOfAKubeConfigContentAreResolvedAgainstTheBaseDirectory(): void
    {
        $client = Client::loadFromKubeConfig(
            content: (string) file_get_contents(__DIR__ . '/fixtures/config/kubeconfig.with_paths.example'),
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
            baseDirectory: __DIR__ . '/fixtures/config/',
        );

        $this->assertSame(__DIR__ . '/fixtures/config/certs/ca.pem', $this->readOption($client, 'caCertificate'));
    }

    public function testAbsolutePathsAreUsedAsIs(): void
    {
        $client = Client::loadFromKubeConfig(
            content: [
                'clusters' => [
                    [
                        'name' => 'cluster',
                        'cluster' => [
                            'server' => 'https://api.example.com',
                            'certificate-authority' => __DIR__ . '/fixtures/config/certs/ca.pem',
                        ],
                    ],
                ],
                'contexts' => [
                    ['name' => 'ctx', 'context' => ['cluster' => 'cluster', 'user' => 'user', 'namespace' => '']],
                ],
                'current-context' => 'ctx',
                'users' => [
                    ['name' => 'user', 'user' => ['token' => 'foo']],
                ],
            ],
            format: FileFormat::Array,
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
            baseDirectory: '/somewhere/else',
        );

        $this->assertSame(__DIR__ . '/fixtures/config/certs/ca.pem', $this->readOption($client, 'caCertificate'));
        $this->assertSame('default', $this->readOption($client, 'namespace'), 'An empty namespace is ignored');
        $this->assertSame('foo', $this->readOption($client, 'token'));
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function provideInvalidUserAttributes(): iterable
    {
        yield 'array token' => [['token' => ['a']], 'token'];
        yield 'empty token' => [['token' => ''], 'token'];
        yield 'array token file' => [['tokenFile' => ['a']], 'tokenFile'];
        yield 'missing token file' => [['tokenFile' => __DIR__ . '/fixtures/config/missing.txt'], 'tokenFile'];
        yield 'array client certificate' => [['client-certificate' => ['a']], 'client-certificate'];
    }

    /**
     * @param array<string, mixed> $user
     */
    #[DataProvider('provideInvalidUserAttributes')]
    public function testInvalidUserAttributesAreRefused(array $user, string $attribute): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . $attribute . '/');

        Client::loadFromKubeConfig(
            content: [
                'clusters' => [
                    ['name' => 'cluster', 'cluster' => ['server' => 'https://api.example.com']],
                ],
                'contexts' => [
                    ['name' => 'ctx', 'context' => ['cluster' => 'cluster', 'user' => 'user']],
                ],
                'current-context' => 'ctx',
                'users' => [
                    ['name' => 'user', 'user' => $user],
                ],
            ],
            format: FileFormat::Array,
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }
}
