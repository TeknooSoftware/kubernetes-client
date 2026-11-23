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

namespace Teknoo\Tests\Kubernetes\HttpClient\Instantiator;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionProperty;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Guzzle::class)]
class GuzzleTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function config(ClientInterface $client): array
    {
        $this->assertInstanceOf(Client::class, $client);

        return new ReflectionProperty(Client::class, 'config')->getValue($client);
    }

    public function testBuild(): void
    {
        $instantiator = new Guzzle();
        $this->assertInstanceOf(Client::class, $instantiator->build(true, 'barca', 'foo', 'bar', 20));
    }

    public function testTheCertificatesAndTheTimeoutAreApplied(): void
    {
        $config = $this->config(new Guzzle()->build(true, '/ca.pem', '/cert.pem', '/key.pem', 20));

        $this->assertSame('/ca.pem', $config['verify']);
        $this->assertSame('/cert.pem', $config['cert']);
        $this->assertSame('/key.pem', $config['ssl_key']);
        $this->assertSame(20, $config['timeout']);
    }

    public function testThePeerIsVerifiedWithTheSystemCertificatesWithoutCaCertificate(): void
    {
        $config = $this->config(new Guzzle()->build(true, null, null, null, null));

        $this->assertTrue($config['verify']);
        $this->assertArrayNotHasKey('cert', $config);
        $this->assertArrayNotHasKey('ssl_key', $config);
        $this->assertArrayNotHasKey('timeout', $config);
    }

    public function testADisabledVerificationIsHonouredEvenWithACaCertificate(): void
    {
        $this->assertFalse($this->config(new Guzzle()->build(false, '/ca.pem', null, null, null))['verify']);
        $this->assertFalse($this->config(new Guzzle()->build(false, null, null, null, null))['verify']);
    }
}
