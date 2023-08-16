<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Symfony\Component\Finder\SplFileInfo;

class FileInspector
{
    private SplFileInfo $file;

    public function __construct(
        SplFileInfo $file
    ) {
        $this->file = $file;
    }

    public function getContents(): string
    {
        return $this->file->getContents();
    }

    public function getLines(): array
    {
        $lines = explode("\n", $this->getContents());
        return array_filter($lines, function($line) {
            $line = trim($line);
            if (empty($line)) {
                return false;
            }

            if (preg_match('#^//#', $line)) {
                return false;
            }

            if (preg_match('#\*#', $line)) {
                return false;
            }

           return true;
        });
    }

    public function getLineCount(): int
    {
        return count($this->getLines());
    }
}
