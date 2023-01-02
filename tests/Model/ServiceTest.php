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

namespace Teknoo\Tests\Kubernetes\Model;

use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\Service;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 *
 * @link        http://teknoo.software/kubernetes-client Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\Model\Service
 * @covers      \Teknoo\Kubernetes\Model\Model
 * @covers      \Teknoo\Kubernetes\Enums\FileFormat
 */
class ServiceTest extends AbstractBaseTestCase
{
    protected function getEmptyFixtureFileName(): string
    {
        return 'services/empty.json';
    }

    protected function getModel(array|string $attributes, FileFormat $format): Model
    {
        return new Service($attributes, $format);
    }
}
