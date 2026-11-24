<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use Yireo\ThemeOverrideChecker\Util\FileStripper;
use PHPUnit\Framework\TestCase;

class FileStripperTest extends TestCase
{
    /**
     * @param string $filename
     * @param string $originalText
     * @param string $expectedText
     * @return void
     */
    #[DataProvider('getTestSnippets')]
    public function testStrip(string $filename, string $originalText, string $expectedText): void
    {
        $fileStripper = new FileStripper();
        $this->assertEquals($expectedText, $fileStripper->strip($filename, $originalText));
    }

    /**
     * @return array<int, array{string, string, string}>
     */
    public static function getTestSnippets(): array
    {
        return [
            [
                'test.html',
                "<!-- Comment -->\n<h1>Hello world</h1>",
                "\n<h1>Hello world</h1>",
            ],
            [
                'test.html',
                "<!-- Comment\n -->\n<h1>Hello world</h1>",
                "\n<h1>Hello world</h1>",
            ],
            [
                'test.xml',
                "<!-- One -->\n<a/>\n<!-- Two -->",
                "\n<a/>\n",
            ],
            [
                'test.js',
                "//test\nconsole.log('Hello');",
                "console.log('Hello');",
            ],
            [
                'test.css',
                "/*\ntest\n*/\nconsole.log('Hello');",
                "\nconsole.log('Hello');",
            ],
        ];
    }

    public function testStripUnterminatedHtmlCommentKeepsText(): void
    {
        $text = '<!--' . str_repeat(" \n", 200000);

        $this->assertSame($text, (new FileStripper())->strip('test.phtml', $text));
    }

    public function testStripLargeHtmlComment(): void
    {
        $text = '<!--' . str_repeat(" \n", 200000) . "-->\n<h1>Hello</h1>";

        $this->assertSame("\n<h1>Hello</h1>", (new FileStripper())->strip('test.phtml', $text));
    }
}
