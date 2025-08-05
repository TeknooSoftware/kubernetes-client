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

namespace Teknoo\Kubernetes\Repository;

use Psr\Http\Message\StreamInterface;
use Teknoo\Kubernetes\Model\PersistentVolume;
use Teknoo\Kubernetes\Collection\PersistentVolumeCollection;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
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
     * @param StreamInterface|string|array<string, mixed>|null $body
     * @return array<string, string|null>
     */
    #[\Override]
    protected function sendRequest(
        RequestMethod $method,
        string $uri,
        array $query = [],
        StreamInterface|string|array|null $body = [],
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
