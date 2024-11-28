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
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */

declare(strict_types=1);

namespace Teknoo\Kubernetes\Repository;

use Teknoo\Kubernetes\Model\Node;
use Teknoo\Kubernetes\Collection\NodeCollection;
use Teknoo\Kubernetes\Enums\RequestMethod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @extends     Repository<Node>
 */
class NodeRepository extends Repository
{
    protected string $uri = 'nodes';

    protected bool $namespace = false;

    protected static ?string $collectionClassName = NodeCollection::class;

    /**
     * @param array<string, string|null> $queryParams
     * @return array<string, string|null>
     */
    public function proxy(Node $node, RequestMethod $method, string $proxyUri, array $queryParams = []): array
    {
        return $this->client->sendRequest(
            method: $method,
            uri: '/' . $this->uri . '/' . $node->getMetadata('name') . '/proxy/' . $proxyUri,
            query: $queryParams,
            body: [],
            namespace: $this->namespace
        );
    }
}
