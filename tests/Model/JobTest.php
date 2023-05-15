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

namespace Teknoo\Tests\Kubernetes\Model;

use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Job;
use Teknoo\Kubernetes\Model\Model;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\Model\Job
 * @covers      \Teknoo\Kubernetes\Model\Model
 * @covers      \Teknoo\Kubernetes\Enums\FileFormat
 */
class JobTest extends AbstractBaseTestCase
{
    protected function getEmptyFixtureFileName(): string
    {
        return 'jobs/empty.json';
    }

    protected function getModel(array|string $attributes, FileFormat $format): Model
    {
        return new Job($attributes, $format);
    }

    protected function getApiVersion(): string
    {
        return 'batch/v1';
    }
}
