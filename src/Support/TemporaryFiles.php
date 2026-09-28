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

namespace Teknoo\Kubernetes\Support;

use function in_array;
use function is_file;
use function unlink;

/**
 * Owns the temporary files (certificates and keys written from inline PEM content or from a kubeconfig)
 * created for a Client. The files are removed when the last owner of this object is released.
 *
 * @internal
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
final class TemporaryFiles
{
    /**
     * @var array<int, string>
     */
    private array $files = [];

    public function add(string $path): void
    {
        $this->files[] = $path;
    }

    public function contains(string $path): bool
    {
        return in_array($path, $this->files, true);
    }

    public function isEmpty(): bool
    {
        return [] === $this->files;
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->files;
    }

    public function remove(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->files = [];
    }

    public function __destruct()
    {
        $this->remove();
    }
}
