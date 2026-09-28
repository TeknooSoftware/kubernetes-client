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

namespace Teknoo\Tests\Kubernetes\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\Pod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Model::class)]
class ModelGetMetadataTest extends TestCase
{
    public function testANameEqualToZeroIsReturned(): void
    {
        $this->assertSame('0', new Pod(['metadata' => ['name' => '0']])->getMetadata('name'));
    }

    public function testAnEmptyNameIsNull(): void
    {
        $this->assertNull(new Pod(['metadata' => ['name' => '']])->getMetadata('name'));
    }

    public function testAMissingMetadataIsNull(): void
    {
        $this->assertNull(new Pod([])->getMetadata('name'));
        $this->assertNull(new Pod(['metadata' => 'invalid'])->getMetadata('name'));
        $this->assertNull(new Pod(['metadata' => ['labels' => ['a' => 'b']]])->getMetadata('name'));
    }

    public function testANonStringValueIsNull(): void
    {
        $this->assertNull(new Pod(['metadata' => ['name' => ['nested']]])->getMetadata('name'));
    }
}
