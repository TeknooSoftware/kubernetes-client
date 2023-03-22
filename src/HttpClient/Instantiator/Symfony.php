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

use Symfony\Component\HttpClient\HttplugClient as SymfonyHttplug;
use Http\Client\HttpClient;
use Teknoo\Kubernetes\HttpClient\InstantiatorInterface;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 */
class Symfony implements InstantiatorInterface
{
    public function build(
        bool $verify,
        ?string $caCertificate,
        ?string $clientCertificate,
        ?string $clientKey,
    ): HttpClient {
        $options = [
            'verify_peer' => $verify,
            'verify_host' => $verify,
        ];

        if (!empty($caCertificate)) {
            $options['cafile'] = $caCertificate;
        }

        if (!empty($clientCertificate)) {
            $options['local_cert'] = $clientCertificate;
        }

        if (!empty($clientKey)) {
            $options['local_pk'] = $clientKey;
        }

        return (new SymfonyHttplug())->withOptions($options);
    }
}
