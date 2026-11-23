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

use GuzzleHttp\Client as GuzzleClient;
use Http\Client\Curl\Client as CurlClient;
use Http\Discovery\Exception\ClassInstantiationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use ReflectionProperty;
use stdClass;
use Teknoo\Kubernetes\Exception\UnsupportedHttpClientOptionsException;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle7;
use Teknoo\Kubernetes\HttpClientDiscovery;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HttpClientDiscovery::class)]
#[CoversClass(UnsupportedHttpClientOptionsException::class)]
class HttpClientDiscoveryOptionsTest extends TestCase
{
    /**
     * @var array<class-string, class-string>
     */
    private array $instantiators;

    protected function setUp(): void
    {
        parent::setUp();
        $this->instantiators = new ReflectionProperty(HttpClientDiscovery::class, 'instantiatorsList')->getValue();
    }

    protected function tearDown(): void
    {
        new ReflectionClass(HttpClientDiscovery::class)->setStaticPropertyValue('instantiatorsList', $this->instantiators);
        parent::tearDown();
    }

    private function removeAllInstantiators(): void
    {
        new ReflectionClass(HttpClientDiscovery::class)->setStaticPropertyValue('instantiatorsList', []);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>}>
     */
    public static function provideUnsupportedOptions(): iterable
    {
        yield 'verify disabled' => [['verify' => false]];
        yield 'ca certificate' => [['caCertificate' => '/ca.pem']];
        yield 'client certificate' => [['clientCertificate' => '/cert.pem']];
        yield 'client key' => [['clientKey' => '/key.pem']];
        yield 'timeout' => [['timeout' => 5]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideUnsupportedOptions')]
    public function testTheFallbackDiscoveryRefusesOptionsItCanNotApply(array $options): void
    {
        $this->removeAllInstantiators();

        $this->expectException(UnsupportedHttpClientOptionsException::class);
        HttpClientDiscovery::find(...$options);
    }

    public function testTheFallbackDiscoveryStillWorksWithoutOptions(): void
    {
        $this->removeAllInstantiators();

        $this->assertInstanceOf(ClientInterface::class, HttpClientDiscovery::find());
    }

    public function testTheFallbackDiscoveryRefusesAClassWhichIsNotAnHttpClient(): void
    {
        $this->removeAllInstantiators();

        $this->expectException(ClassInstantiationFailedException::class);
        HttpClientDiscovery::find(clientClass: stdClass::class);
    }

    /**
     * @param array<class-string, class-string> $instantiators
     */
    private function useInstantiators(array $instantiators): void
    {
        new ReflectionClass(HttpClientDiscovery::class)->setStaticPropertyValue('instantiatorsList', $instantiators);
    }

    public function testTheNativeGuzzleClientHasAnInstantiatorByDefault(): void
    {
        $default = new ReflectionProperty(HttpClientDiscovery::class, 'instantiatorsList')->getDefaultValue();

        $this->assertArrayHasKey(GuzzleClient::class, $default);
        $this->assertSame(Guzzle::class, $default[GuzzleClient::class]);
    }

    public function testTheNativeGuzzleClientReceivesTheTlsAndTimeoutOptions(): void
    {
        $this->useInstantiators([GuzzleClient::class => Guzzle::class]);

        $client = HttpClientDiscovery::find(caCertificate: '/ca.pem', timeout: 20);

        $this->assertInstanceOf(GuzzleClient::class, $client);
        $config = new ReflectionProperty(GuzzleClient::class, 'config')->getValue($client);
        $this->assertSame('/ca.pem', $config['verify']);
        $this->assertSame(20, $config['timeout']);
    }

    public function testARegisteredInstantiatorIsPreferredToTheNativeGuzzleClient(): void
    {
        $this->useInstantiators([GuzzleClient::class => Guzzle::class]);
        HttpClientDiscovery::registerInstantiator(stdClass::class, Curl::class);

        $this->assertInstanceOf(CurlClient::class, HttpClientDiscovery::find(caCertificate: '/ca.pem'));
    }

    public function testTheClientClassOfTheGuzzle7AdapterGivesANativeGuzzleClientWithTheOptions(): void
    {
        $this->useInstantiators(['Http\\Adapter\\Guzzle7\\Client' => Guzzle7::class]);

        $client = HttpClientDiscovery::find(
            caCertificate: '/ca.pem',
            timeout: 20,
            clientClass: 'Http\\Adapter\\Guzzle7\\Client',
        );

        $this->assertInstanceOf(GuzzleClient::class, $client);
        $config = new ReflectionProperty(GuzzleClient::class, 'config')->getValue($client);
        $this->assertSame('/ca.pem', $config['verify']);
        $this->assertSame(20, $config['timeout']);
    }
}
