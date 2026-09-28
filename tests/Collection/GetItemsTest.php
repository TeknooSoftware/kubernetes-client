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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Collection\Collection;
use Teknoo\Kubernetes\Collection\PodCollection;
use Teknoo\Kubernetes\Model\Node;
use Teknoo\Kubernetes\Model\Pod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Collection::class)]
#[CoversClass(PodCollection::class)]
class GetItemsTest extends TestCase
{
    public function testAnOverriddenModelClassIsUsedToBuildTheItems(): void
    {
        $collection = new class ([['metadata' => ['name' => 'a']], new Node(['metadata' => ['name' => 'b']])]) extends PodCollection {
            public static function getModelClass(): string
            {
                return Node::class;
            }
        };

        $this->assertContainsOnlyInstancesOf(Node::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function testTheSourceArrayIsNotAltered(): void
    {
        $items = [['metadata' => ['name' => 'a']], ['metadata' => ['name' => 'b']]];
        $copy = $items;

        $collection = new PodCollection($items);
        foreach ($collection as $model) {
            $this->assertInstanceOf(Pod::class, $model);
        }

        $items[1]['metadata']['name'] = 'changed';
        $this->assertSame('b', $collection->last()->getMetadata('name'));
        $this->assertSame($copy[0], $items[0]);
    }
}
