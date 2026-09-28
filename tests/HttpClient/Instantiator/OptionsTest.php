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

namespace Teknoo\Tests\Kubernetes\HttpClient\Instantiator;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\Curl\Client as CurlClient;
use Http\Client\Socket\Client as SocketClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\HttpClient\HttplugClient;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle7;
use Teknoo\Kubernetes\HttpClient\Instantiator\Socket;
use Teknoo\Kubernetes\HttpClient\Instantiator\Symfony;

use const CURLOPT_CAINFO;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_SSLCERT;
use const CURLOPT_SSLKEY;
use const CURLOPT_TIMEOUT;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Curl::class)]
#[CoversClass(Socket::class)]
#[CoversClass(Guzzle7::class)]
#[CoversClass(Symfony::class)]
class OptionsTest extends TestCase
{
    /**
     * @return array<int, mixed>
     */
    private function curlOptions(bool $verify, ?string $ca = '/ca.pem', ?int $timeout = 20): array
    {
        $client = new Curl()->build($verify, $ca, '/cert.pem', '/key.pem', $timeout);
        $this->assertInstanceOf(CurlClient::class, $client);

        return new ReflectionProperty(CurlClient::class, 'curlOptions')->getValue($client);
    }

    public function testCurlVerifyHostIsTwoOrZeroNeverTheDeprecatedOne(): void
    {
        $options = $this->curlOptions(true);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame('/ca.pem', $options[CURLOPT_CAINFO]);
        $this->assertSame('/cert.pem', $options[CURLOPT_SSLCERT]);
        $this->assertSame('/key.pem', $options[CURLOPT_SSLKEY]);
        $this->assertSame(20, $options[CURLOPT_TIMEOUT]);

        $options = $this->curlOptions(false);
        $this->assertSame(0, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertFalse($options[CURLOPT_SSL_VERIFYPEER]);
    }

    /**
     * @return array<string, mixed>
     */
    private function socketConfig(bool $verify, ?int $timeout = 20): array
    {
        $client = new Socket()->build($verify, '/ca.pem', '/cert.pem', '/key.pem', $timeout);
        $this->assertInstanceOf(SocketClient::class, $client);

        return new ReflectionProperty(SocketClient::class, 'config')->getValue($client);
    }

    public function testSocketTimeoutIsSetInMillisecondsAtTheTopLevel(): void
    {
        $config = $this->socketConfig(true, 20);

        $this->assertSame(20000, $config['timeout']);
        $this->assertArrayNotHasKey('http', $config['stream_context_options']);
        $this->assertSame('/ca.pem', $config['stream_context_options']['ssl']['cafile']);
        $this->assertSame('/cert.pem', $config['stream_context_options']['ssl']['local_cert']);
        $this->assertSame('/key.pem', $config['stream_context_options']['ssl']['local_pk']);
    }

    public function testSocketVerifyDisablesBothPeerAndPeerNameChecks(): void
    {
        $config = $this->socketConfig(true);
        $this->assertTrue($config['stream_context_options']['ssl']['verify_peer']);
        $this->assertTrue($config['stream_context_options']['ssl']['verify_peer_name']);

        $config = $this->socketConfig(false);
        $this->assertFalse($config['stream_context_options']['ssl']['verify_peer']);
        $this->assertFalse($config['stream_context_options']['ssl']['verify_peer_name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function guzzleConfig(bool $verify, ?string $ca): array
    {
        $client = new Guzzle7()->build($verify, $ca, '/cert.pem', '/key.pem', 20);
        $this->assertInstanceOf(Guzzle7Client::class, $client);

        $guzzle = new ReflectionProperty(Guzzle7Client::class, 'guzzle')->getValue($client);
        $this->assertInstanceOf(GuzzleClient::class, $guzzle);

        return new ReflectionProperty(GuzzleClient::class, 'config')->getValue($guzzle);
    }

    public function testGuzzleHonoursADisabledVerificationEvenWithACaCertificate(): void
    {
        $this->assertSame('/ca.pem', $this->guzzleConfig(true, '/ca.pem')['verify']);
        $this->assertTrue($this->guzzleConfig(true, null)['verify']);
        $this->assertFalse($this->guzzleConfig(false, '/ca.pem')['verify']);
        $this->assertFalse($this->guzzleConfig(false, null)['verify']);
        $this->assertSame(20, $this->guzzleConfig(true, null)['timeout']);
        $this->assertSame('/cert.pem', $this->guzzleConfig(true, null)['cert']);
        $this->assertSame('/key.pem', $this->guzzleConfig(true, null)['ssl_key']);
    }

    /**
     * @return array<string, mixed>
     */
    private function symfonyOptions(bool $verify): array
    {
        $client = new Symfony()->build($verify, '/ca.pem', '/cert.pem', '/key.pem', 20);
        $this->assertInstanceOf(HttplugClient::class, $client);

        $inner = new ReflectionProperty(HttplugClient::class, 'client')->getValue($client);

        return new ReflectionProperty($inner::class, 'defaultOptions')->getValue($inner);
    }

    public function testSymfonyOptions(): void
    {
        $options = $this->symfonyOptions(true);
        $this->assertTrue($options['verify_peer']);
        $this->assertTrue($options['verify_host']);
        $this->assertSame('/ca.pem', $options['cafile']);
        $this->assertSame('/cert.pem', $options['local_cert']);
        $this->assertSame('/key.pem', $options['local_pk']);
        $this->assertSame(20.0, (float) $options['timeout']);

        $options = $this->symfonyOptions(false);
        $this->assertFalse($options['verify_peer']);
        $this->assertFalse($options['verify_host']);
    }
}
