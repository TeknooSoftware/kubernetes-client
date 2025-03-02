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

namespace Teknoo\Tests\Kubernetes\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Collection\ConfigMapCollection;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\ConfigMap;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Repository\ConfigMapRepository;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(ConfigMapRepository::class)]
#[CoversClass(Repository::class)]
class ConfigMapRepositoryTest extends AbstractBaseTestCase
{
    protected function getRepository(): Repository
    {
        return new ConfigMapRepository(
            $this->getClientMock(),
        );
    }

    protected function getCollectionClassName(): string
    {
        return ConfigMapCollection::class;
    }

    protected function getModel(): Model
    {
        return new ConfigMap(
            [
                'metadata' => [
                    'name' => 'foo',
                ]
            ]
        );
    }
}
