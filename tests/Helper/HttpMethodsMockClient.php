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
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
