<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Util\FileInspector;
use Yireo\ThemeOverrideChecker\Util\FileInspectorFactory;
use Yireo\ThemeOverrideChecker\Util\FileStripper;
use Yireo\ThemeOverrideChecker\Util\OverrideAdviser;

class OverrideAdviserTest extends TestCase
{
    private string $baseDir = '';

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/override-adviser-' . uniqid();
        mkdir($this->baseDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->baseDir . '/*') ?: []);
        rmdir($this->baseDir);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function getFileNames(): array
    {
        return [
            'template' => ['file.phtml'],
            'layout' => ['file.xml'],
            'stylesheet' => ['file.less'],
        ];
    }

    #[DataProvider('getFileNames')]
    public function testAdviseWarnsAboutFilesThatAreTheSameAsTheOriginal(string $fileName): void
    {
        $themeFile = $this->createFile('theme-' . $fileName, "<div>\nHello\n</div>");
        $parentFile = $this->createFile('parent-' . $fileName, "<div>\nHello\n</div>");

        $advice = $this->getOverrideAdviser()->advise($themeFile, $parentFile);

        $this->assertStringContainsString('File is the same as the original', $advice);
    }

    public function testAdviseReportsDifferentLines(): void
    {
        $themeFile = $this->createFile('theme.phtml', "<div>\nHello\n</div>");
        $parentFile = $this->createFile('parent.phtml', "<div>\nWorld\n</div>");

        $advice = $this->getOverrideAdviser()->advise($themeFile, $parentFile, 1, 0);

        $this->assertStringContainsString('Found 1 lines to be different', $advice);
    }

    private function getOverrideAdviser(): OverrideAdviser
    {
        $fileInspectorFactory = $this->createStub(FileInspectorFactory::class);
        $fileInspectorFactory->method('create')->willReturnCallback(
            fn (array $data) => new FileInspector($data['file'], new FileStripper())
        );

        return new OverrideAdviser($fileInspectorFactory);
    }

    private function createFile(string $fileName, string $contents): SplFileInfo
    {
        file_put_contents($this->baseDir . '/' . $fileName, $contents);

        return new SplFileInfo($this->baseDir . '/' . $fileName, '', $fileName);
    }
}
