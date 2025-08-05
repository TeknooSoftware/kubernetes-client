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

namespace Teknoo\Tests\Kubernetes\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Endpoint;
use Teknoo\Kubernetes\Model\Model;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(Endpoint::class)]
#[CoversClass(Model::class)]
class EndpointTest extends AbstractBaseTestCase
{
    protected function getEmptyFixtureFileName(): string
    {
        return 'endpoints/empty.json';
    }

    protected function getModel(array|string $attributes, FileFormat $format): Model
    {
        return new Endpoint($attributes, $format);
    }
}
