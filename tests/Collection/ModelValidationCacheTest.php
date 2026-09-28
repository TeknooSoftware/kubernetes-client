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

namespace Teknoo\Tests\Kubernetes\Collection;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Model\Pod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Collection::class)]
class ModelValidationCacheTest extends TestCase
{
    public function testAValidatedCollectionKeepsBuildingModels(): void
    {
        $collection = new class ([['metadata' => ['name' => 'a']]]) extends Collection {
            protected static ?string $modelClassName = Pod::class;
        };

        $this->assertSame(Pod::class, $collection::getModelClass());
        $this->assertSame(Pod::class, $collection::getModelClass());
        $this->assertContainsOnlyInstancesOf(Pod::class, new $collection([['metadata' => ['name' => 'b']]]));
    }

    public function testAnInvalidCollectionIsRefusedOnEveryCall(): void
    {
        $collection = new class ([]) extends Collection {
            protected static ?string $modelClassName = null;

            public function __construct(array $items)
            {
                // Bypass the parent constructor to declare the class without validating the model
            }
        };

        $errors = 0;
        for ($i = 0; $i < 2; ++$i) {
            try {
                $collection::getModelClass();
            } catch (LogicException) {
                ++$errors;
            }
        }

        $this->assertSame(2, $errors);
    }

    public function testAnInvalidModelClassIsRefused(): void
    {
        $this->expectException(LogicException::class);
        new class ([]) extends Collection {
            protected static ?string $modelClassName = stdClass::class;
        };
    }
}
