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
use Psr\Http\Client\ClientInterface;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
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
class ClientOptionsUpdateTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/kc-options-' . bin2hex(random_bytes(6));
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

    private function createClient(): Client
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(new Response(200, [], '{"items":[]}'));

        return new Client(
            options: [
                'master' => 'https://api.example.com',
                'client_cert' => 'inline-cert',
                'client_key' => 'inline-key',
            ],
            httpClient: $httpClient,
        );
    }

    /**
     * @return iterable<string, array{0: array<string, string|int|bool>}>
     */
    public static function provideOptionsRequiringARebuild(): iterable
    {
        yield 'ca_cert' => [['ca_cert' => 'new-ca']];
        yield 'client_cert' => [['client_cert' => 'new-cert']];
        yield 'client_key' => [['client_key' => 'new-key']];
        yield 'timeout' => [['timeout' => 5]];
        yield 'verify' => [['verify' => false]];
    }

    /**
     * @param array<string, string|int|bool> $options
     */
    #[DataProvider('provideOptionsRequiringARebuild')]
    public function testTheHttpClientIsRebuiltWhenATlsOrTimeoutOptionChanges(array $options): void
    {
        $client = $this->createClient();
        $property = new ReflectionProperty(Client::class, 'httpMethodsClient');

        $client->sendRequest(RequestMethod::Get, '/pods');
        $this->assertNotNull($property->getValue($client));

        $client->setOptions($options);
        $this->assertNull($property->getValue($client), 'The HTTP client must be rebuilt on the next request');

        $client->sendRequest(RequestMethod::Get, '/pods');
        $this->assertNotNull($property->getValue($client));
    }

    public function testTheHttpClientIsKeptWhenOnlyOtherOptionsChange(): void
    {
        $client = $this->createClient();
        $property = new ReflectionProperty(Client::class, 'httpMethodsClient');

        $client->sendRequest(RequestMethod::Get, '/pods');
        $built = $property->getValue($client);

        $client->setOptions(['namespace' => 'other', 'token' => 'foo', 'master' => 'https://api2.example.com']);

        $this->assertSame($built, $property->getValue($client));
    }

    public function testANewInlineCertificateIsWrittenAndUsedAfterTheChange(): void
    {
        $client = $this->createClient();

        $client->sendRequest(RequestMethod::Get, '/pods');
        $this->assertCount(2, $this->listTemporaryFiles());

        $client->setOptions(['client_cert' => 'new-cert-content']);
        $client->sendRequest(RequestMethod::Get, '/pods');

        $files = $this->listTemporaryFiles();
        $this->assertCount(3, $files);
        $contents = array_map(static fn (string $file): string => (string) file_get_contents($file), $files);
        $this->assertContains('new-cert-content', $contents);

        unset($client);
        gc_collect_cycles();
        $this->assertSame([], $this->listTemporaryFiles());
    }
}
