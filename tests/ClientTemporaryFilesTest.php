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

use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\Psr17FactoryDiscovery;
use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Exception\MissingMasterOptionException;
use Teknoo\Kubernetes\Exception\WriteErrorException;
use Teknoo\Kubernetes\Support\TemporaryFiles;

use function array_map;
use function basename;
use function bin2hex;
use function file_get_contents;
use function file_put_contents;
use function fileperms;
use function gc_collect_cycles;
use function glob;
use function is_writable;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sort;
use function str_starts_with;
use function sys_get_temp_dir;
use function unlink;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
#[CoversClass(TemporaryFiles::class)]
class ClientTemporaryFilesTest extends TestCase
{
    private const string CA_CERT = "-----BEGIN CERTIFICATE-----\nca\n-----END CERTIFICATE-----";

    private const string CLIENT_CERT = "-----BEGIN CERTIFICATE-----\nclient\n-----END CERTIFICATE-----";

    private const string CLIENT_KEY = "-----BEGIN PRIVATE KEY-----\nkey\n-----END PRIVATE KEY-----";

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/kc-test-' . bin2hex(random_bytes(6));
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

        $files = glob($this->directory . '/.*');

        if (false === $files) {

            $files = [];

        }


        foreach ($files as $file) {
            if (!str_ends_with($file, '/.') && !str_ends_with($file, '/..')) {
                @rmdir($file);
            }
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
        sort($files);

        return $files;
    }

    private function createHttpClientMock(): ClientInterface
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"items":[]}'));

        return $httpClient;
    }

    /**
     * @param array<string, string> $expectedContents indexed by file name prefix
     */
    private function assertTemporaryFiles(array $expectedContents): void
    {
        $files = $this->listTemporaryFiles();
        $this->assertCount(count($expectedContents), $files);

        foreach ($expectedContents as $prefix => $content) {
            $matching = array_values(
                array_filter(
                    $files,
                    static fn (string $file): bool => str_starts_with(basename($file), $prefix),
                ),
            );

            $this->assertCount(1, $matching, "Expected exactly one file with the prefix $prefix");
            $this->assertSame($content, file_get_contents($matching[0]));
            $this->assertSame(0o600, fileperms($matching[0]) & 0o777, "The file $prefix must be private");
        }
    }

    public function testFilesFromKubeConfigAreRemovedWhenTheClientIsReleased(): void
    {
        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );

        $this->assertTemporaryFiles([
            'kubernetes-client-ca-cert.pem' => 'foo-data',
            'kubernetes-client-client-cert.pem' => 'foo-data',
            'kubernetes-client-client-key.pem' => 'foo-data',
        ]);

        unset($client);
        gc_collect_cycles();

        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testFilesFromKubeConfigAreRemovedEvenWhenRepositoriesWereUsed(): void
    {
        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createHttpClientMock(),
        );

        $this->assertSame([], $client->pods()->find()->all());
        $this->assertCount(3, $this->listTemporaryFiles());

        unset($client);
        gc_collect_cycles();

        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testInlineCertificatesAreWrittenOnFirstRequestAndRemovedWhenTheClientIsReleased(): void
    {
        $client = new Client(
            options: [
                'master' => 'https://api.example.com',
                'ca_cert' => self::CA_CERT,
                'client_cert' => self::CLIENT_CERT,
                'client_key' => self::CLIENT_KEY,
            ],
            httpClient: $this->createHttpClientMock(),
        );

        $this->assertSame([], $this->listTemporaryFiles());

        $this->assertSame(['items' => []], $client->sendRequest(RequestMethod::Get, '/pods'));

        $this->assertTemporaryFiles([
            'kubernetes-client-ca-cert' => self::CA_CERT,
            'kubernetes-client-client-cert' => self::CLIENT_CERT,
            'kubernetes-client-client-key' => self::CLIENT_KEY,
        ]);

        $this->assertSame(['items' => []], $client->sendRequest(RequestMethod::Get, '/pods'));
        $this->assertCount(3, $this->listTemporaryFiles(), 'Files must be reused between requests');

        unset($client);
        gc_collect_cycles();

        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testFilesAreRemovedImmediatelyWhenOptionsAreReset(): void
    {
        $client = new Client(
            options: [
                'master' => 'https://api.example.com',
                'client_cert' => self::CLIENT_CERT,
                'client_key' => self::CLIENT_KEY,
            ],
            httpClient: $this->createHttpClientMock(),
        );

        $client->sendRequest(RequestMethod::Get, '/pods');
        $this->assertCount(2, $this->listTemporaryFiles());

        $client->setOptions(['master' => 'https://api.example.com'], true);

        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testAcloneKeepsTheFilesUntilTheLastInstanceIsReleased(): void
    {
        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );

        $clone = clone $client;
        $this->assertCount(3, $this->listTemporaryFiles());

        unset($client);
        gc_collect_cycles();
        $this->assertCount(3, $this->listTemporaryFiles(), 'The clone still uses the files');

        unset($clone);
        gc_collect_cycles();
        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testFilesAreRemovedWhenTheClientCanNotBeBuiltFromTheKubeConfig(): void
    {
        $thrown = null;
        try {
            Client::loadFromKubeConfigFile(
                filePath: __DIR__ . '/fixtures/config/kubeconfig.empty_server.example',
                httpClient: $this->createStub(ClientInterface::class),
                httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
            );
        } catch (MissingMasterOptionException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(MissingMasterOptionException::class, $thrown);
        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testFilesWrittenWithACustomNameFunctionArePrivate(): void
    {
        Client::setTmpNameFunction(
            static function (string $dir, string $file): string {
                $path = $dir . '/' . $file;
                file_put_contents($path, '');

                return $path;
            },
        );

        $client = Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );

        $this->assertSame(
            [
                $this->directory . '/kubernetes-client-ca-cert.pem',
                $this->directory . '/kubernetes-client-client-cert.pem',
                $this->directory . '/kubernetes-client-client-key.pem',
            ],
            $this->listTemporaryFiles(),
        );

        foreach ($this->listTemporaryFiles() as $file) {
            $this->assertSame(0o600, fileperms($file) & 0o777);
        }

        unset($client);
        gc_collect_cycles();
        $this->assertSame([], $this->listTemporaryFiles());
    }

    public function testAnErrorIsThrownWhenTheTemporaryFileCanNotBeCreated(): void
    {
        Client::setTmpNameFunction(static fn (): bool => false);

        $this->expectException(WriteErrorException::class);
        Client::loadFromKubeConfigFile(
            filePath: __DIR__ . '/fixtures/config/kubeconfig.example',
            httpClient: $this->createStub(ClientInterface::class),
            httpStreamFactory: $this->createStub(StreamFactoryInterface::class),
        );
    }

    public function testSetTmpDirRefusesAFile(): void
    {
        $file = $this->directory . '/file';
        file_put_contents($file, '');

        $this->expectException(InvalidArgumentException::class);
        Client::setTmpDir($file);
    }

    public function testSetTmpDirRefusesANonWritableDirectory(): void
    {
        $readOnly = $this->directory . '/read-only';
        mkdir($readOnly, 0500);

        if (is_writable($readOnly)) {
            $this->markTestSkipped('The current user can write anywhere, the check can not be tested.');
        }

        $this->expectException(InvalidArgumentException::class);
        Client::setTmpDir($readOnly);
    }

    public function testTheHttpClientIsBuiltWithTheTemporaryFilePaths(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"kind":"PodList","items":[]}'));

        $client = new Client(
            options: [
                'master' => 'https://api.example.com',
                'ca_cert' => self::CA_CERT,
            ],
            httpClient: $httpClient,
            httpRequestFactory: Psr17FactoryDiscovery::findRequestFactory(),
            httpStreamFactory: Psr17FactoryDiscovery::findStreamFactory(),
        );

        $this->assertSame(['kind' => 'PodList', 'items' => []], $client->sendRequest(RequestMethod::Get, '/pods'));
        $this->assertSame(
            ['kubernetes-client-ca-cert'],
            array_map(
                static fn (string $file): string => substr(basename($file), 0, 25),
                $this->listTemporaryFiles(),
            ),
        );
    }
}
