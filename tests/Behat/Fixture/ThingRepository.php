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
 *
 * @link        https://teknoo.software/libraries/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\Kubernetes\Behat\Fixture;

use Teknoo\Kubernetes\Repository\Repository;

/**
 * Repository of a custom resource used by the Behat suite, overriding the api version resolution
 *
 * @extends Repository<Thing>
 */
class ThingRepository extends Repository
{
    public static string $apiGroup = 'custom.io/v1';

    protected string $uri = 'things';

    protected static ?string $collectionClassName = ThingCollection::class;

    protected static function getApiVersion(): ?string
    {
        return static::$apiGroup;
    }
}
