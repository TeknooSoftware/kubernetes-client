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
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes;

use Http\Client\HttpClient;
use Http\Client\Curl\Client as CurlClient;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\Socket\Client as SocketClient;
use Http\Discovery\ClassDiscovery;
use Http\Discovery\Exception\ClassInstantiationFailedException;
use Http\Discovery\Exception\DiscoveryFailedException;
use Http\Discovery\Exception\NotFoundException;
use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplug;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle7;
use Teknoo\Kubernetes\HttpClient\Instantiator\Socket;
use Teknoo\Kubernetes\HttpClient\Instantiator\Symfony;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

use function is_string;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class HttpClientDiscovery extends ClassDiscovery
{
    /**
     * @var array<class-string<HttpClient>, class-string<InstantiatorInterface>>
     */
    private static $instantiatorsList = [
        CurlClient::class => Curl::class,
        Guzzle7Client::class => Guzzle7::class,
        SocketClient::class => Socket::class,
        SymfonyHttplug::class => Symfony::class,
    ];

    /**
     * @param class-string<HttpClient> $clientClass
     * @param class-string<InstantiatorInterface> $instantiatorClass
     */
    public static function registerInstantiator(string $clientClass, string $instantiatorClass): void
    {
        self::$instantiatorsList[$clientClass] = $instantiatorClass;
    }

    /**
     * Finds an HTTP Client.
     *
     * @throws NotFoundException
     */
    public static function find(
        bool $verify = true,
        ?string $caCertificate = null,
        ?string $clientCertificate = null,
        ?string $clientKey = null,
        ?int $timeout = null,
    ): HttpClient {
        try {
            $clientClass = static::findOneByType(HttpClient::class);
            // @codeCoverageIgnoreStart
        } catch (DiscoveryFailedException $e) {
            throw new NotFoundException(
                'No HTTPlug clients found. Make sure to install a package providing "php-http/client-implementation". '
                    . 'Example: "php-http/guzzle6-adapter".',
                0,
                $e
            );
            // @codeCoverageIgnoreEnd
        }

        return static::instantiateClass(
            class: $clientClass,
            verify: $verify,
            caCertificate: $caCertificate,
            clientCertificate: $clientCertificate,
            clientKey: $clientKey,
            timeout: $timeout,
        );
    }

    /**
     * @param string|callable $class
     * @throws ClassInstantiationFailedException
     */
    protected static function instantiateClass(
        $class,
        bool $verify = true,
        ?string $caCertificate = null,
        ?string $clientCertificate = null,
        ?string $clientKey = null,
        ?int $timeout = null,
    ): HttpClient {
        if (is_string($class) && isset(self::$instantiatorsList[$class])) {
            $instantiator = self::$instantiatorsList[$class];
            return (new $instantiator())->build(
                verify: $verify,
                caCertificate: $caCertificate,
                clientCertificate: $clientCertificate,
                clientKey: $clientKey,
                timeout: $timeout,
            );
        }

        // @codeCoverageIgnoreStart
        return parent::instantiateClass($class);
        // @codeCoverageIgnoreEnd
    }
}
