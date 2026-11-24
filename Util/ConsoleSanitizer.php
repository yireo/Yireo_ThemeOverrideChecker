<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Symfony\Component\Console\Formatter\OutputFormatter;

class ConsoleSanitizer
{
    /**
     * Remove terminal control characters and escape console formatter tags
     *
     * @param mixed $value
     * @return string
     */
    public function clean(mixed $value): string
    {
        $text = (string)$value;
        $text = (string)preg_replace('/[\x00-\x08\x0A-\x1F\x7F]|\xC2[\x80-\x9F]/', '', $text);

        return OutputFormatter::escape($text);
    }
}
