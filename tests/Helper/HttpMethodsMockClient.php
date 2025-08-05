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

namespace Teknoo\Tests\Kubernetes\Helper;

use Http\Client\Common\HttpMethodsClientInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Use this class in unit tests to mock more easily
/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
class HttpMethodsMockClient implements HttpMethodsClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function get($uri, array $headers = []): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function head($uri, array $headers = []): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function trace($uri, array $headers = []): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function post($uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function put($uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function patch($uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function delete($uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function options($uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }

    public function send(string $method, $uri, array $headers = [], $body = null): ResponseInterface
    {
        return new Response(200, [], 'dummy-mock');
    }
}
