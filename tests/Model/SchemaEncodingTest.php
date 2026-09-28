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
use Teknoo\Kubernetes\Model\ConfigMap;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\Pod;

use function json_decode;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Model::class)]
class SchemaEncodingTest extends TestCase
{
    public function testStringValuesHoldingBracketsAreNotAltered(): void
    {
        $configMap = new ConfigMap([
            'metadata' => ['name' => 'cfg'],
            'data' => ['config.yml' => "items: []\nother: {}\n"],
        ]);

        $decoded = json_decode($configMap->getSchema(), true);

        $this->assertSame("items: []\nother: {}\n", $decoded['data']['config.yml']);
    }

    public function testEmptyMapsAreEncodedAsObjectsAndListsStayLists(): void
    {
        $pod = new Pod([
            'metadata' => ['name' => 'p', 'labels' => []],
            'spec' => ['containers' => [['name' => 'c', 'ports' => [['containerPort' => 80]]]], 'volumes' => [[]]],
        ]);

        $schema = $pod->getSchema();

        $this->assertStringContainsString('"labels": {}', $schema);
        $this->assertStringContainsString("\"volumes\": [\n            []\n        ]", $schema);
        $this->assertSame(
            [
                'kind' => 'Pod',
                'apiVersion' => 'v1',
                'metadata' => ['name' => 'p', 'labels' => []],
                'spec' => [
                    'containers' => [['name' => 'c', 'ports' => [['containerPort' => 80]]]],
                    'volumes' => [[]],
                ],
            ],
            json_decode($schema, true),
        );
    }

    public function testSchemaStaysPrettyPrintedWithUnescapedSlashes(): void
    {
        $pod = new Pod(['metadata' => ['name' => 'p', 'annotations' => ['a/b' => 'c/d']]]);

        $this->assertSame(
            "{\n    \"kind\": \"Pod\",\n    \"apiVersion\": \"v1\",\n    \"metadata\": {\n        \"name\": \"p\",\n"
            . "        \"annotations\": {\n            \"a/b\": \"c/d\"\n        }\n    }\n}",
            $pod->getSchema(),
        );
    }
}
