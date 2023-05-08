<?php

/*
 * Kubernetes Client.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
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

namespace Teknoo\Kubernetes\HttpClient\Instantiator;

use Http\Client\Socket\Client;
use Psr\Http\Client\ClientInterface;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
class Socket implements InstantiatorInterface
{
    public function build(
        bool $verify,
        ?string $caCertificate,
        ?string $clientCertificate,
        ?string $clientKey,
        ?int $timeout,
    ): ClientInterface {
        $options = [
            'stream_context_options' => [
                'ssl' => [
                    'verify_peer' => $verify,
                ],
            ]
        ];

        if (!empty($caCertificate)) {
            $options['stream_context_options']['ssl']['cafile'] = $caCertificate;
        }

        if (!empty($clientCertificate)) {
            $options['stream_context_options']['ssl']['local_cert'] = $clientCertificate;
        }

        if (!empty($clientKey)) {
            $options['stream_context_options']['ssl']['local_pk'] = $clientKey;
        }

        if (!empty($timeout)) {
            $options['stream_context_options']['http']['timeout'] = (int) $timeout;
        }

        return new Client($options);
    }
}
