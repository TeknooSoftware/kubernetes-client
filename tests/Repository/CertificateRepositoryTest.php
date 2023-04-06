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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
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

use Teknoo\Kubernetes\Collection\CertificateCollection;
use Teknoo\Kubernetes\Model\Certificate;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Repository\CertificateRepository;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 *
 * @covers      \Teknoo\Kubernetes\Repository\CertificateRepository
 * @covers      \Teknoo\Kubernetes\Repository\Strategy\PatchMergeTrait
 * @covers      \Teknoo\Kubernetes\Repository\Repository
 * @covers      \Teknoo\Kubernetes\Enums\RequestMethod
 * @covers      \Teknoo\Kubernetes\Enums\PatchType
 */
class CertificateRepositoryTest extends AbstractBaseTestCase
{
    protected function getRepository(): Repository
    {
        return new CertificateRepository(
            $this->getClientMock(),
        );
    }

    protected function getCollectionClassName(): string
    {
        return CertificateCollection::class;
    }

    protected function getModel(): Model
    {
        return new Certificate(
            [
                'metadata' => [
                    'name' => 'foo',
                ]
            ]
        );
    }
}
