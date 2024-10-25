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

namespace Teknoo\Tests\Kubernetes\Collection;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Collection\IssuerCollection;
use Teknoo\Kubernetes\Model\Issuer;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(Collection::class)]
#[CoversClass(IssuerCollection::class)]
class IssuerCollectionTest extends AbstractBaseTestCase
{
    protected function getCollection(?array $query = null, ?string $continue = null): Collection
    {
        return new IssuerCollection(
            [
                [],
                new Issuer(),
                [],
            ],
            $this->createMock(Repository::class),
            $query,
            $continue,
        );
    }

    protected function getModelClassName(): string
    {
        return Issuer::class;
    }
}
