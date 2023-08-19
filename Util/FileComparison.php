<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Differ\DifferFactory;

class FileComparison
{
    private DifferFactory $differFactory;
    private FileInspectorFactory $fileInspectorFactory;

    /**
     * @param DifferFactory $differFactory
     * @param FileInspectorFactory $fileInspectorFactory
     */
    public function __construct(
        DifferFactory $differFactory,
        FileInspectorFactory $fileInspectorFactory
    ) {
        $this->differFactory = $differFactory;
        $this->fileInspectorFactory = $fileInspectorFactory;
    }

    /**
     * @param SplFileInfo $newFile
     * @param SplFileInfo|null $originalFile
     * @return string
     */
    public function getDiff(SplFileInfo $newFile, ?SplFileInfo $originalFile = null): string
    {
        if ($originalFile === null) {
            return '';
        }

        $differBuilderOptions = ['fromFile' => $originalFile->getRealPath(), 'toFile' => $newFile->getRealPath()];
        $differ = $this->differFactory->create($differBuilderOptions);

        return $differ->diff(
            $originalFile->getContents(),
            $newFile->getContents(),
        );
    }

    /**
     * @param SplFileInfo $newFile
     * @param SplFileInfo|null $originalFile
     * @return int
     */
    public function getLineDifference(SplFileInfo $newFile, ?SplFileInfo $originalFile = null): int
    {
        if ($originalFile === null) {
            return 0;
        }

        $originalFileInspector = $this->getFileInspector($originalFile);
        $newFileInspector = $this->getFileInspector($newFile);

        return count(
            array_diff(
                $originalFileInspector->getLines(),
                $newFileInspector->getLines()
            )
        );
    }

    /**
     * @param SplFileInfo $newFile
     * @param SplFileInfo|null $originalFile
     * @return int
     */
    public function getLineCountDifference(SplFileInfo $newFile, ?SplFileInfo $originalFile = null): int
    {
        if ($originalFile === null) {
            return 0;
        }

        $originalFileInspector = $this->getFileInspector($originalFile);
        $newFileInspector = $this->getFileInspector($newFile);

        return abs($originalFileInspector->getLineCount() - $newFileInspector->getLineCount());
    }

    /**
     * @param SplFileInfo $newFile
     * @param SplFileInfo|null $originalFile
     * @return int
     */
    public function getPercentageDifference(SplFileInfo $newFile, ?SplFileInfo $originalFile = null): int
    {
        if ($originalFile === null) {
            return 100;
        }

        $originalFileLineCount = $this->getFileInspector($originalFile)->getLineCount();
        $newFileLineCount = $this->getFileInspector($newFile)->getLineCount();
        $lineDifference = $this->getLineDifference($newFile, $originalFile);
        $lineCount = $this->getLineCountDifference($newFile, $originalFile);
        if ($lineDifference < $lineCount) {
            $lineDifference = $lineCount;
        }

        if ($originalFileLineCount < 1 || $lineDifference < 1) {
            return 0;
        }

        return (int)($newFileLineCount / $originalFileLineCount * $lineDifference * 100);
    }

    /**
     * @param SplFileInfo $file
     * @return FileInspector
     */
    private function getFileInspector(SplFileInfo $file): FileInspector
    {
        return $this->fileInspectorFactory->create(['file' => $file]);
    }
}
