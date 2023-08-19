<?php

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use Yireo\ThemeOverrideChecker\Util\FileStripper;
use PHPUnit\Framework\TestCase;

class FileStripperTest extends TestCase
{
    /**
     * @dataProvider getTestSnippets
     * @param string $originalText
     * @param string $expectedText
     * @return void
     */
    public function testStrip(string $filename, string $originalText, string $expectedText)
    {
        $fileStripper = new FileStripper();
        $this->assertEquals($expectedText, $fileStripper->strip($filename, $originalText));
    }

    public function getTestSnippets(): array
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
}
