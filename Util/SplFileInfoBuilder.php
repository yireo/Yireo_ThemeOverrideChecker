<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Finder\SplFileInfo;

class SplFileInfoBuilder
{
    private DirectoryList $directoryList;

    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    public function create(string $file, ?string $relativePath = ''): SplFileInfo
    {
        if (empty($relativePath)) {
            $relativePath = $this->directoryList->getRoot();
        }

        $basePath = rtrim($relativePath, '/') . '/';
        $relativePathname = str_starts_with($file, $basePath) ? substr($file, strlen($basePath)) : $file;

        return new SplFileInfo($file, $relativePath, trim($relativePathname, '/'));
    }
}
