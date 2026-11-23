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

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Override;
use Psr\Http\Client\ClientInterface;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

/**
 * Instantiator of the native Guzzle client (Guzzle 7 and 8 implement PSR-18, no adapter is needed)
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class Guzzle implements InstantiatorInterface
{
    #[Override]
    public function build(
        bool $verify,
        ?string $caCertificate,
        ?string $clientCertificate,
        ?string $clientKey,
        ?int $timeout,
    ): ClientInterface {
        $options = [
            RequestOptions::VERIFY => $verify,
        ];

        if ($verify && !empty($caCertificate)) {
            $options[RequestOptions::VERIFY] = $caCertificate;
        }

        if (!empty($clientCertificate)) {
            $options[RequestOptions::CERT] = $clientCertificate;
        }

        if (!empty($clientKey)) {
            $options[RequestOptions::SSL_KEY] = $clientKey;
        }

        if (!empty($timeout)) {
            $options[RequestOptions::TIMEOUT] = (int) $timeout;
        }

        return new Client($options);
    }
}
