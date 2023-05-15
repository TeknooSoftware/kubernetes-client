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
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Repository;

use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Collection\PodCollection;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @extends     Repository<Pod>
 */
class PodRepository extends Repository
{
    protected string $uri = 'pods';

    protected static ?string $collectionClassName = PodCollection::class;

    /**
     * @param array<string, string|null> $queryParams
     */
    public function logs(Pod $pod, array $queryParams = []): string
    {
        return $this->client->sendStringableRequest(
            method: RequestMethod::Get,
            uri: '/' . $this->uri . '/' . $pod->getMetadata('name') . '/log',
            query: $queryParams,
        );
    }

    /**
     * @param array<string, string|null> $queryParams
     */
    public function exec(Pod $pod, array $queryParams = []): string
    {
        return $this->client->sendStringableRequest(
            method: RequestMethod::Post,
            uri: '/' . $this->uri . '/' . $pod->getMetadata('name') . '/exec',
            query: $queryParams
        );
    }
}
