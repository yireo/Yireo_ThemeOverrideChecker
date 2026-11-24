<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Yireo\ThemeOverrideChecker\Exception\ThemeFileResolveException;

class PathGuard
{
    /**
     * @param string $relativePath
     * @return void
     * @throws ThemeFileResolveException
     */
    public function assertSafeRelativePath(string $relativePath): void
    {
        $normalizedPath = str_replace('\\', '/', $relativePath);
        $segments = explode('/', $normalizedPath);

        if ($relativePath === ''
            || str_contains($relativePath, "\0")
            || str_starts_with($normalizedPath, '/')
            || in_array('..', $segments, true)
        ) {
            throw new ThemeFileResolveException(__('Invalid relative file path "%1"', $relativePath));
        }
    }

    /**
     * @param string $path
     * @param string $baseDir
     * @return bool
     */
    public function isInside(string $path, string $baseDir): bool
    {
        $realBaseDir = realpath($baseDir);
        $realPath = realpath($path);
        if ($realBaseDir === false || $realPath === false || !is_file($realPath)) {
            return false;
        }

        return str_starts_with($realPath, rtrim($realBaseDir, '/') . '/');
    }
}
