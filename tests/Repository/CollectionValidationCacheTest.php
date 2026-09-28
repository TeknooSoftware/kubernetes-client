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

namespace Teknoo\Tests\Kubernetes\Repository;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Teknoo\Kubernetes\Client;
use Teknoo\Kubernetes\Collection\PodCollection;
use Teknoo\Kubernetes\Repository\Repository;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Repository::class)]
class CollectionValidationCacheTest extends TestCase
{
    private function createClient(int $expectedRequests): Client
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->exactly($expectedRequests))
            ->method('sendRequest')
            ->willReturn(['items' => []]);

        return $client;
    }

    public function testAValidatedRepositoryKeepsWorkingOnRepeatedRequests(): void
    {
        $repository = new class ($this->createClient(3)) extends Repository {
            protected string $uri = 'things';

            protected static ?string $collectionClassName = PodCollection::class;
        };

        $this->assertCount(0, $repository->find());
        $this->assertCount(0, $repository->find());
        $this->assertNull($repository->first());
    }

    public function testAnInvalidRepositoryIsRefusedOnEveryRequest(): void
    {
        $repository = new class ($this->createClient(0)) extends Repository {
            protected string $uri = 'things';

            protected static ?string $collectionClassName = stdClass::class;
        };

        $errors = 0;
        for ($i = 0; $i < 2; ++$i) {
            try {
                $repository->find();
            } catch (LogicException) {
                ++$errors;
            }
        }

        $this->assertSame(2, $errors);
    }

    public function testTheValidationOfARepositoryDoesNotLeakToAnother(): void
    {
        $valid = new class ($this->createClient(1)) extends Repository {
            protected string $uri = 'things';

            protected static ?string $collectionClassName = PodCollection::class;
        };
        $this->assertCount(0, $valid->find());

        $invalid = new class ($this->createClient(0)) extends Repository {
            protected string $uri = 'things';

            protected static ?string $collectionClassName = null;
        };

        $this->expectException(LogicException::class);
        $invalid->find();
    }
}
