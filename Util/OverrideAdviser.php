<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Symfony\Component\Finder\SplFileInfo;

class OverrideAdviser
{
    private FileInspectorFactory $fileInspectorFactory;

    public function __construct(
        \Yireo\ThemeOverrideChecker\Util\FileInspectorFactory $fileInspectorFactory
    ) {
        $this->fileInspectorFactory = $fileInspectorFactory;
    }

    /**
     * @param SplFileInfo $themeFile
     * @param SplFileInfo|null $parentThemeFile
     * @param int $lineDiff
     * @param int $lineCountDiff
     * @return string
     */
    public function advise(
        SplFileInfo $themeFile,
        ?SplFileInfo $parentThemeFile = null,
        int $lineDiff = 0,
        int $lineCountDiff = 0
    ): string {

        if ($lineDiff < 1) {
            $lineDiff = $lineCountDiff;
        }

        $themeFileLineCount = $this->getFileLineCount($themeFile);
        $parentThemeFileLineCount = $this->getFileLineCount($parentThemeFile);
        if ($parentThemeFileLineCount < 1) {
            $parentThemeFile = null;
        }

        if (strstr($themeFile->getRelativePathname(), 'etc/view.xml')) {
            if ($themeFileLineCount > 200) {
                return '<error>Only override those definitions that are different and remove all others</error>';
            }

            return 'Skipped file';
        }

        if ($this->skipFileByFilename($themeFile)) {
            return 'Skipped file';
        }

        if ($this->warnIfSameAsOriginal($themeFile, $parentThemeFile)) {
            return '<error>File is the same as the original, so perhaps just remove it</error>';
        }

        if ($this->isLayoutFile($themeFile) && $lineDiff > 0) {
            return 'Inspect the XML layout instructions manually.';
        }

        if ($lineDiff > 0) {
            return '<comment>Found '.$lineDiff.' lines to be different. Inspect them manually.</comment>';
        }

        return 'No differences';
    }

    /**
     * @param SplFileInfo|null $file
     * @return int
     */
    private function getFileLineCount(?SplFileInfo $file = null): int
    {
        if ($file === null) {
            return 0;
        }

        $fileInspector = $this->fileInspectorFactory->create(['file' => $file]);
        return $fileInspector->getLineCount();
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    private function skipFileByFilename(SplFileInfo $file): bool
    {
        if (preg_match('/\.(md|txt)$/', $file->getFilename())) {
            return true;
        }

        if (in_array($file->getFilename(), [
            'composer.json',
            'registration.php',
            'theme.xml',
        ])) {
            return true;
        }

        return false;
    }

    /**
     * @param SplFileInfo $file
     * @param SplFileInfo|null $originalFile
     * @return bool
     */
    private function warnIfSameAsOriginal(SplFileInfo $file, ?SplFileInfo $originalFile = null): bool
    {
        if ($originalFile === null) {
            return false;
        }

        if ($file->getContents() !== $originalFile->getContents()) {
            return false;
        }

        if (false === preg_match('/\.(csv|xml|svg|jpg|gif|png|eot|ttf|woff|woff2)$/', $file->getFilename())) {
            return false;
        }

        return true;
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    private function isLayoutFile(SplFileInfo $file): bool
    {
        return preg_match('/\.xml$/', $file->getFilename()) && strstr($file->getRelativePathname(), '/layout/');
    }

    /**
     * @param SplFileInfo $file
     * @return bool
     */
    private function isViewXmlFile(SplFileInfo $file): bool
    {
        return $file->getFilename() === 'view.xml';
    }
}
