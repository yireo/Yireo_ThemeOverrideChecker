<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Yireo\ThemeOverrideChecker\Util\ConsoleSanitizer;

class ConsoleSanitizerTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function getValues(): array
    {
        return [
            'plain text' => ['Magento_Theme/templates/a.phtml', 'Magento_Theme/templates/a.phtml'],
            'console tag' => ['<error>Fake</error>', '<error>Fake</error>'],
            'hyperlink tag' => ['<href=https://example.com>click</>', '<href=https://example.com>click</>'],
            'ANSI escape' => ["\e[31mred\e[0m", '[31mred[0m'],
            'OSC 52 clipboard' => ["\e]52;c;aGk=\x07", ']52;c;aGk='],
            'C1 control' => ["a\xC2\x9B31mb", 'a31mb'],
            'tab is kept' => ["a\tb", "a\tb"],
            'trailing backslash' => ['path\\', 'path\\'],
        ];
    }

    #[DataProvider('getValues')]
    public function testClean(string $value, string $expectedOutput): void
    {
        $formatter = new OutputFormatter(false);
        $cleaned = (new ConsoleSanitizer())->clean($value);

        $this->assertSame($expectedOutput, $formatter->format($cleaned));
    }

    public function testCleanCastsScalars(): void
    {
        $this->assertSame('42', (new ConsoleSanitizer())->clean(42));
    }
}
