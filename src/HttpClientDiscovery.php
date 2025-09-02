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

namespace Teknoo\Kubernetes;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Curl\Client as CurlClient;
use Http\Adapter\Guzzle7\Client as Guzzle7Client;
use Http\Client\Socket\Client as SocketClient;
use Http\Discovery\ClassDiscovery;
use Http\Discovery\Exception\ClassInstantiationFailedException;
use Http\Discovery\Exception\DiscoveryFailedException;
use Http\Discovery\Exception\NotFoundException;
use Override;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplug;
use Teknoo\Kubernetes\HttpClient\Instantiator\Curl;
use Teknoo\Kubernetes\HttpClient\Instantiator\Guzzle7;
use Teknoo\Kubernetes\HttpClient\Instantiator\Socket;
use Teknoo\Kubernetes\HttpClient\Instantiator\Symfony;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

use function array_keys;
use function class_exists;
use function is_string;
use function is_array;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class HttpClientDiscovery extends ClassDiscovery
{
    /**
     * @var array<class-string<ClientInterface>, class-string<InstantiatorInterface>>
     */
    private static array $instantiatorsList = [
        CurlClient::class => Curl::class,
        Guzzle7Client::class => Guzzle7::class,
        SocketClient::class => Socket::class,
        SymfonyHttplug::class => Symfony::class,
    ];

    /**
     * @param class-string<ClientInterface> $clientClass
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
        ?string $clientClass = null,
    ): ClientInterface {
        if (null === $clientClass) {
            foreach (array_keys(self::$instantiatorsList) as $class) {
                if (class_exists($class)) {
                    $clientClass = $class;

                    break;
                }
            }
        }

        if (null === $clientClass) {
            try {
                $clientClass = static::findOneByType(ClientInterface::class);
                // @codeCoverageIgnoreStart
            } catch (DiscoveryFailedException $e) {
                throw new NotFoundException(
                    'No HTTPlug clients found. Make sure to install a package providing '
                    . '"php-http/client-implementation". Example: "php-http/guzzle7-adapter".',
                    0,
                    $e
                );
                // @codeCoverageIgnoreEnd
            }
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
    #[Override]
    protected static function instantiateClass(
        $class,
        bool $verify = true,
        ?string $caCertificate = null,
        ?string $clientCertificate = null,
        ?string $clientKey = null,
        ?int $timeout = null,
    ): ClientInterface {
        $className = $class;
        if (is_array($className) && is_string($className[0])) {
            $className = $className[0];
        }

        if (is_string($className) && isset(self::$instantiatorsList[$className])) {
            $instantiator = self::$instantiatorsList[$className];
            return new $instantiator()->build(
                verify: $verify,
                caCertificate: $caCertificate,
                clientCertificate: $clientCertificate,
                clientKey: $clientKey,
                timeout: $timeout,
            );
        }

        return parent::instantiateClass($class);
    }
}
