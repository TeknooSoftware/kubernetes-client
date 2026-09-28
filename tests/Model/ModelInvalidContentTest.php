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

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Enums\FileFormat;
use Teknoo\Kubernetes\Model\Model;
use Teknoo\Kubernetes\Model\Pod;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(Model::class)]
class ModelInvalidContentTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: FileFormat}>
     */
    public static function provideScalarDocuments(): iterable
    {
        yield 'json string' => ['"123"', FileFormat::Json];
        yield 'json number' => ['42', FileFormat::Json];
        yield 'json null' => ['null', FileFormat::Json];
        yield 'yaml scalar' => ['foo', FileFormat::Yaml];
        yield 'yaml empty' => ['', FileFormat::Yaml];
        yield 'yaml null' => ['~', FileFormat::Yaml];
    }

    #[DataProvider('provideScalarDocuments')]
    public function testAScalarDocumentIsRefusedWithAnInvalidArgumentException(string $content, FileFormat $format): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must decode to an array/');

        new Pod($content, $format);
    }

    public function testAStringGivenAsArrayAttributesIsReportedAsSuch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Array attributes must be provided as an array/');

        new Pod('{"metadata": {}}', FileFormat::Array);
    }

    public function testAValidYamlDocumentIsStillAccepted(): void
    {
        $pod = new Pod("metadata:\n  name: my-pod\n", FileFormat::Yaml);

        $this->assertSame('my-pod', $pod->getMetadata('name'));
    }
}
