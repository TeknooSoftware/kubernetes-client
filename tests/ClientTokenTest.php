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

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Tests\Kubernetes\Helper\HttpMethodsMockClient;

use const PHP_EOL;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Client::class)]
class ClientTokenTest extends TestCase
{
    private function createClient(string $token, bool $expectRequest): Client
    {
        $client = new Client(['master' => 'https://api.example.com', 'token' => $token]);

        $mockHttpMethodsClient = $this->createMock(HttpMethodsMockClient::class);
        if ($expectRequest) {
            $mockHttpMethodsClient
                ->expects($this->once())
                ->method('send')
                ->with('GET', 'https://api.example.com/api/v1/namespaces/default/pods', $this->callback(
                    function (array $headers): bool {
                        $this->assertSame('Bearer ' . $this->expectedBearer, $headers['Authorization']);

                        return true;
                    }
                ), null)
                ->willReturn(new Response(200, [], '{"items":[]}'));
        } else {
            $mockHttpMethodsClient
                ->expects($this->never())
                ->method('send');
        }

        new ReflectionProperty(Client::class, 'httpMethodsClient')->setValue($client, $mockHttpMethodsClient);

        return $client;
    }

    private string $expectedBearer = '';

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function provideAcceptedTokens(): iterable
    {
        yield 'jwt' => ['eyJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJrOHMifQ.sig', 'eyJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJrOHMifQ.sig'];
        yield 'token with a colon' => ['user:p4ss', 'user:p4ss'];
        yield 'token with spaces around' => ['  padded  ', 'padded'];
        yield 'file with eol at end' => [__DIR__ . '/fixtures/tokens/auth_with_eol_at_end', 'foobar'];
    }

    #[DataProvider('provideAcceptedTokens')]
    public function testAcceptedTokensAreSentAsBearer(string $token, string $bearer): void
    {
        $this->expectedBearer = $bearer;
        $client = $this->createClient($token, true);

        $this->assertSame(['items' => []], $client->sendRequest(RequestMethod::Get, '/pods'));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideRefusedTokenPaths(): iterable
    {
        yield 'phar' => ['phar://evil.phar/token'];
        yield 'file' => ['file:///etc/passwd'];
        yield 'http' => ['http://foo.bar/token'];
        yield 'data' => ['data://text/plain;base64,Zm9v'];
        yield 'ftp' => ['ftp://user:pass@host/token'];
        yield 'glob' => ['glob://*'];
    }

    #[DataProvider('provideRefusedTokenPaths')]
    public function testStreamWrapperUrlsAreRefusedAsTokenPath(string $token): void
    {
        $client = $this->createClient($token, false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/stream wrapper/');
        $client->sendRequest(RequestMethod::Get, '/pods');
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideMultilineTokens(): iterable
    {
        yield 'line feed' => ["secret-first-line\nsecret-second-line"];
        yield 'carriage return' => ["secret-first-line\rsecret-second-line"];
        yield 'crlf' => ["secret-first-line\r\nsecret-second-line"];
        yield 'php eol' => ['secret-first-line' . PHP_EOL . 'secret-second-line'];
    }

    #[DataProvider('provideMultilineTokens')]
    public function testMultilineTokensAreRefusedWithoutLeakingTheSecret(string $token): void
    {
        $client = $this->createClient($token, false);

        $thrown = null;
        try {
            $client->sendRequest(RequestMethod::Get, '/pods');
        } catch (InvalidArgumentException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $thrown);
        $this->assertStringNotContainsString('secret-first-line', $thrown->getMessage());
        $this->assertStringNotContainsString('secret-second-line', $thrown->getMessage());
    }

    public function testAMultilineTokenFileIsRefusedNamingTheFileButNotItsContent(): void
    {
        $path = __DIR__ . '/fixtures/tokens/auth_with_eol_in_middle';
        $client = $this->createClient($path, false);

        $thrown = null;
        try {
            $client->sendRequest(RequestMethod::Get, '/pods');
        } catch (InvalidArgumentException $error) {
            $thrown = $error;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $thrown);
        $this->assertStringContainsString($path, $thrown->getMessage());
        $this->assertStringNotContainsString('foo', $thrown->getMessage());
    }
}
