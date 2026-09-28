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

namespace Teknoo\Tests\Kubernetes\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\Kubernetes\Support\TemporaryFiles;

use function bin2hex;
use function file_put_contents;
use function mkdir;
use function random_bytes;
use function rmdir;
use function sys_get_temp_dir;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TemporaryFiles::class)]
class TemporaryFilesTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/kc-support-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        $files = glob($this->directory . '/*');
        if (false === $files) {
            $files = [];
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        rmdir($this->directory);
        parent::tearDown();
    }

    public function testRemoveDeletesRegisteredFilesAndIgnoresMissingOnes(): void
    {
        $existing = $this->directory . '/existing';
        file_put_contents($existing, 'content');
        $missing = $this->directory . '/missing';

        $files = new TemporaryFiles();
        $this->assertTrue($files->isEmpty());
        $this->assertSame([], $files->all());

        $files->add($existing);
        $files->add($missing);

        $this->assertFalse($files->isEmpty());
        $this->assertTrue($files->contains($existing));
        $this->assertTrue($files->contains($missing));
        $this->assertFalse($files->contains($this->directory . '/other'));
        $this->assertSame([$existing, $missing], $files->all());

        $files->remove();

        $this->assertFileDoesNotExist($existing);
        $this->assertTrue($files->isEmpty());
        $this->assertFalse($files->contains($existing));
    }

    public function testFilesAreRemovedWhenTheOwnerIsReleased(): void
    {
        $existing = $this->directory . '/existing';
        file_put_contents($existing, 'content');

        $files = new TemporaryFiles();
        $files->add($existing);
        $this->assertFileExists($existing);

        unset($files);

        $this->assertFileDoesNotExist($existing);
    }
}
