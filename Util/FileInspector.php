<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Symfony\Component\Finder\SplFileInfo;

class FileInspector
{
    private SplFileInfo $file;
    private FileStripper $fileStripper;

    public function __construct(
        SplFileInfo $file,
        FileStripper $fileStripper
    ) {
        $this->file = $file;
        $this->fileStripper = $fileStripper;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->file->getContents();
    }

    /**
     * @return string[]
     */
    public function getLines(): array
    {
        $contents = $this->getContents();
        $contents = $this->fileStripper->strip($this->file->getFilename(), $contents);
        $lines = explode("\n", $contents);
        return array_filter($lines, function($line) {
            $line = trim($line);
            if (empty($line)) {
                return false;
            }

           return true;
        });
    }

    /**
     * @return int
     */
    public function getLineCount(): int
    {
        return count($this->getLines());
    }
}
