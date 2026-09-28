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

namespace Teknoo\Kubernetes\HttpClient\Instantiator;

use Http\Client\Curl\Client;
use Override;
use Psr\Http\Client\ClientInterface;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

use function curl_share_init;
use function curl_share_setopt;

use const CURL_LOCK_DATA_CONNECT;
use const CURL_LOCK_DATA_DNS;
use const CURL_LOCK_DATA_SSL_SESSION;
use const CURLOPT_CAINFO;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SHARE;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_SSLCERT;
use const CURLOPT_SSLKEY;
use const CURLOPT_TIMEOUT;
use const CURLSHOPT_SHARE;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
class Curl implements InstantiatorInterface
{
    #[Override]
    public function build(
        bool $verify,
        ?string $caCertificate,
        ?string $clientCertificate,
        ?string $clientKey,
        ?int $timeout,
    ): ClientInterface {
        $verifyHost = 0;
        if ($verify) {
            $verifyHost = 2;
        }

        // curl-client creates a handle per request: a share handle lets the connections, the DNS cache and the
        // TLS sessions be reused across requests, avoiding a TLS handshake at each call
        $share = curl_share_init();
        curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_CONNECT);
        curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
        curl_share_setopt($share, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);

        $options = [
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verifyHost,
            CURLOPT_SHARE => $share,
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
