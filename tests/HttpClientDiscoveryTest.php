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

use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\Curl\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use stdClass;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl;
use Teknoo\Kubernetes\HttpClientDiscovery;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(HttpClientDiscovery::class)]
class HttpClientDiscoveryTest extends TestCase
{
    public function testFind(): void
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            HttpClientDiscovery::find()
        );
    }
    public function testFindWithInstatiator(): void
    {
        HttpClientDiscovery::registerInstantiator(stdClass::class, Curl::class);

        $this->assertInstanceOf(
            Client::class,
            HttpClientDiscovery::find(clientClass: \stdClass::class)
        );
    }

    public function testFindSpecificClientClass(): void
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            HttpClientDiscovery::find(clientClass: Guzzle7Client::class)
        );
    }

    public function testFindWithNoInstantiators(): void
    {
        // Use reflection to clear the instantiators list
        $refClass = new ReflectionClass(HttpClientDiscovery::class);
        $refClass->setStaticPropertyValue('instantiatorsList', []);

        $client = HttpClientDiscovery::find();

        $this->assertInstanceOf(
            ClientInterface::class,
            $client
        );
    }
}