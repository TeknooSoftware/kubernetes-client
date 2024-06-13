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

namespace Teknoo\Tests\Kubernetes\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\Kubernetes\Collection\PodCollection;
use Teknoo\Kubernetes\Enums\PatchType;
use Teknoo\Kubernetes\Enums\RequestMethod;
use Teknoo\Kubernetes\Model\Pod;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Repository\PodRepository;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
#[CoversClass(PodRepository::class)]
#[CoversClass(PatchType::class)]
#[CoversClass(RequestMethod::class)]
#[CoversClass(Repository::class)]
class PodRepositoryTest extends AbstractBaseTestCase
{
    protected function getRepository(): Repository
    {
        return new PodRepository(
            $this->getClientMock(),
        );
    }

    protected function getCollectionClassName(): string
    {
        return PodCollection::class;
    }

    protected function getModel(): Model
    {
        return new Pod(
            [
                'metadata' => [
                    'name' => 'foo',
                ]
            ]
        );
    }

    public function testLogs()
    {
        $repository = $this->getRepository();
        self::assertIsString(
            $repository->logs($this->getModel(), ['foo' => 'bar']),
        );
    }

    public function testExec()
    {
        $repository = $this->getRepository();
        self::assertIsString(
            $repository->exec($this->getModel(), ['foo' => 'bar']),
        );
    }
}
