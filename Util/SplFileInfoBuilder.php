<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Finder\SplFileInfo;

class SplFileInfoBuilder
{
    private DirectoryList $directoryList;
    private ObjectManagerInterface $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager,
        DirectoryList $directoryList
    ) {
        $this->objectManager = $objectManager;
        $this->directoryList = $directoryList;
    }

    public function create(string $file, ?string $relativePath = ''): SplFileInfo
    {
        if (empty($relativePath)) {
            $relativePath = $this->directoryList->getRoot();
        }

        $args = [
            'file' => $file,
            'relativePath' => $relativePath,
            'relativePathname' => trim(str_replace($relativePath, '', $file), '/'),
        ];

        return $this->objectManager->create(SplFileInfo::class, $args);
    }
}
