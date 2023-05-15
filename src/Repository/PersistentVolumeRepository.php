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

use Teknoo\Kubernetes\Model\PersistentVolume;
use Teknoo\Kubernetes\Collection\PersistentVolumeCollection;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @extends     Repository<PersistentVolume>
 */
class PersistentVolumeRepository extends Repository
{
    protected string $uri = 'persistentvolumes';

    protected static ?string $collectionClassName = PersistentVolumeCollection::class;

    /**
     * @param array<string, string|null> $query
     * @return array<string, string|null>
     */
    protected function sendRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        mixed $body = [],
        bool $namespace = true,
        ?PatchType $patchType = null,
    ): array {
        return parent::sendRequest(
            method: $method,
            uri: $uri,
            query: $query,
            body: $body,
            namespace: false,
            patchType: $patchType,
        );
    }
}
