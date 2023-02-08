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

namespace Teknoo\Kubernetes\HttpClient\Instantiator;

use Http\Client\HttpClient;
use Http\Client\Socket\Client;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */
class Socket implements InstantiatorInterface
{
    public function build(
        bool $verify,
        ?string $clientCertificate,
        ?string $clientKey,
    ): HttpClient {
        $options = [
            'stream_context_options' => [
                'ssl' => [
                    'verify_peer' => $verify,
                ],
            ]
        ];

        if ($clientCertificate) {
            $options['stream_context_options']['ssl']['local_cert'] = $clientCertificate;
        }

        if ($clientKey) {
            $options['stream_context_options']['ssl']['local_pk'] = $clientKey;
        }

        return new Client($options);
    }
}
