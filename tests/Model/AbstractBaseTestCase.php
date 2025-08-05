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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Attribute\Explorer;
use Teknoo\Kubernetes\Model\Model;

use function dirname;
use function file_exists;
use function file_get_contents;
use function json_encode;
use function str_replace;
use function trim;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @copyright   Copyright (c) Marc Lough ( https://github.com/maclof/kubernetes-client )
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 * @author      Marc Lough <http://maclof.com>
 */
abstract class AbstractBaseTestCase extends PHPUnitTestCase
{
    abstract protected function getEmptyFixtureFileName(): string;

    abstract protected function getModel(array|string $attributes, FileFormat $format): Model;

    protected function getApiVersion(): string
    {
        return 'v1';
    }

    protected function getFixture(string $path): ?string
    {
        $path = dirname(__DIR__, 1) . '/fixtures/' . $path;

        if (!file_exists($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        // Fix for windows encoded fixtures.
        $contents = str_replace("\r\n", "\n", $contents);

        return trim($contents, ' ' . PHP_EOL);
    }

    public function testCronstructionArrayWithWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getModel('', FileFormat::Array);
    }

    public function testCronstructionJsonWithWrongType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getModel([], FileFormat::Json);
    }

    public function testCronstructionJsonWithMalFormedString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getModel('aa', FileFormat::Json);
    }

    public function testCronstructionYamlWithWrongType(): void
    {

        $this->expectException(InvalidArgumentException::class);
        $this->getModel([], FileFormat::Yaml);
    }

    public function testCronstructionYamlWithMalFormedString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getModel('@', FileFormat::Yaml);
    }

    public function testGetSchema(): void
    {
        $model = $this->getModel([], FileFormat::Array);

        $schema = trim($model->getSchema());
        $toString = trim((string) $model);
        $fixture = trim((string) $this->getFixture($this->getEmptyFixtureFileName()));

        $this->assertEquals($fixture, $schema);
        $this->assertEquals($fixture, $toString);
    }

    public function testToArrayFromArray(): void
    {
        $model = $this->getModel(
            $a = [
                'metadata' => [
                    'name' => 'test',
                ],
                'spec' => [
                    'foo' => 'bar'
                ]
            ],
            FileFormat::Array
        );

        $this->assertEquals($a, $model->toArray());
    }

    public function testExplore(): void
    {
        $model = $this->getModel(
            $a = [
                'metadata' => [
                    'name' => 'test',
                ],
                'spec' => [
                    'foo' => 'bar'
                ]
            ],
            FileFormat::Array
        );

        $this->assertInstanceOf(Explorer::class, $explorer = $model->explore());

        $explorer->metadata->name = 'test2';
        $this->assertEquals($a, $model->toArray());

        $this->assertEquals([
            'metadata' => [
                'name' => 'test2',
            ],
            'spec' => [
                'foo' => 'bar'
            ]
        ], $explorer->getModel()->toArray());
    }

    public function testToArrayFromJson(): void
    {
        $model = $this->getModel(
            json_encode(
                $a = [
                    'metadata' => [
                        'name' => 'test',
                    ],
                    'spec' => [
                        'foo' => 'bar'
                    ]
                ]
            ),
            FileFormat::Json
        );

        $this->assertEquals($a, $model->toArray());
    }

    public function testToArrayFromYaml(): void
    {
        $model = $this->getModel(
            Yaml::dump(
                $a = [
                    'metadata' => [
                        'name' => 'test',
                    ],
                    'spec' => [
                        'foo' => 'bar'
                    ]
                ]
            ),
            FileFormat::Yaml
        );

        $this->assertEquals($a, $model->toArray());
    }

    public function testGetMetadata(): void
    {
        $model = $this->getModel(
            [
                'metadata' => [
                    'name' => 'test',
                    'foo' => ['bar']
                ],
            ],
            FileFormat::Array
        );

        $this->assertEquals('test', $model->getMetadata('name'));

        $this->assertNull($model->getMetadata('foo'));

        $this->assertNull($model->getMetadata('bar'));
    }

    public function testGetApiVersion(): void
    {
        $modelClass = $this->getModel(
            $a1 = [
                'metadata' => [
                    'name' => 'test',
                ],
            ],
            FileFormat::Array
        )::class;
        $this->assertIsString($modelClass::getApiVersion());
    }

    public function testUpdateModel(): void
    {
        $model1 = $this->getModel(
            $a1 = [
                'metadata' => [
                    'name' => 'test',
                ],
            ],
            FileFormat::Array
        );

        $a2 = [
            'metadata' => [
                'name' => 'test',
            ],
            'spec' => [
                'foo' => 'bar',
            ]
        ];

        $this->assertInstanceOf($model1::class, $model2 = $model1->updateModel(
            function (array $value) use ($a1, $a2): array {
                $this->assertEquals($a1, $value);

                return $a2;
            }
        ));
        $this->assertEquals($a1, $model1->toArray());
        $this->assertEquals($a2, $model2->toArray());
    }
}
