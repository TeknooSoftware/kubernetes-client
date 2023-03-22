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

use Http\Client\Curl\Client;
use Http\Client\HttpClient;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

use const CURLOPT_CAINFO;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_SSLCERT;
use const CURLOPT_SSLKEY;
use const CURLOPT_TIMEOUT;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */
class Curl implements InstantiatorInterface
{
    public function build(
        bool $verify,
        ?string $caCertificate,
        ?string $clientCertificate,
        ?string $clientKey,
        ?int $timeout,
    ): HttpClient {
        $options = [
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify,
        ];

        if (!empty($caCertificate)) {
            $options[CURLOPT_CAINFO] = $caCertificate;
        }

        if (!empty($clientCertificate)) {
            $options[CURLOPT_SSLCERT] = $clientCertificate;
        }

        if (!empty($clientKey)) {
            $options[CURLOPT_SSLKEY] = $clientKey;
        }

        if (!empty($timeout)) {
            $options[CURLOPT_TIMEOUT] = $timeout;
        }

        return new Client(
            null,
            null,
            $options,
        );
    }
}
