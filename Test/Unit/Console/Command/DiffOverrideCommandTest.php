<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\View\Design\Theme\ThemePackage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Console\Command\DiffOverrideCommand;
use Yireo\ThemeOverrideChecker\Util\ConsoleSanitizer;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\PathGuard;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class DiffOverrideCommandTest extends TestCase
{
    private string $baseDir = '';

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/diff-override-' . uniqid();
        mkdir($this->baseDir . '/theme/templates', 0777, true);
        file_put_contents($this->baseDir . '/theme/templates/file.phtml', 'theme');
        file_put_contents($this->baseDir . '/secret.php', 'secret');
    }

    protected function tearDown(): void
    {
        @unlink($this->baseDir . '/theme/templates/file.phtml');
        @unlink($this->baseDir . '/secret.php');
        @rmdir($this->baseDir . '/theme/templates');
        @rmdir($this->baseDir . '/theme');
        @rmdir($this->baseDir);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function getUnsafeFiles(): array
    {
        return [
            'parent traversal' => ['../secret.php'],
            'absolute path' => ['/etc/passwd'],
        ];
    }

    #[DataProvider('getUnsafeFiles')]
    public function testRejectsFilesOutsideTheme(string $file): void
    {
        $themeFileResolver = $this->createMock(ThemeFileResolver::class);
        $themeFileResolver->expects($this->never())->method('resolveOriginalFile');

        $commandTester = new CommandTester($this->getCommand($themeFileResolver));
        $commandTester->execute(['theme' => 'Yireo/child', 'file' => $file]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringNotContainsString('secret', $commandTester->getDisplay());
    }

    public function testEscapesDiffOutput(): void
    {
        $themeFileResolver = $this->createStub(ThemeFileResolver::class);
        $themeFileResolver->method('resolveOriginalFile')->willReturn(
            new SplFileInfo($this->baseDir . '/theme/templates/file.phtml', '', 'file.phtml')
        );

        $fileComparison = $this->createStub(FileComparison::class);
        $fileComparison->method('getDiff')->willReturn(
            "+<href=https://example.com>click</>\n-\e]52;c;aGk=\x07\n <error>context</error>"
        );

        $commandTester = new CommandTester($this->getCommand($themeFileResolver, $fileComparison));
        $commandTester->execute(['theme' => 'Yireo/child', 'file' => 'templates/file.phtml']);

        $display = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('+<href=https://example.com>click</>', $display);
        $this->assertStringContainsString('-]52;c;aGk=', $display);
        $this->assertStringContainsString(' <error>context</error>', $display);
        $this->assertStringNotContainsString("\e", $display);
    }

    private function getCommand(
        ThemeFileResolver $themeFileResolver,
        ?FileComparison $fileComparison = null
    ): DiffOverrideCommand {
        $themeProvider = $this->createStub(ThemeProvider::class);
        $themeProvider->method('getTheme')->willReturn(
            new ThemePackage('frontend/Yireo/child', $this->baseDir . '/theme')
        );
        $themeProvider->method('getThemePath')->willReturn($this->baseDir . '/theme');

        $splFileInfoBuilder = $this->createStub(SplFileInfoBuilder::class);
        $splFileInfoBuilder->method('create')->willReturnCallback(
            fn (string $file) => new SplFileInfo($file, '', basename($file))
        );

        return new DiffOverrideCommand(
            $themeFileResolver,
            $themeProvider,
            $fileComparison ?? $this->createStub(FileComparison::class),
            $splFileInfoBuilder,
            $this->createStub(State::class),
            new PathGuard(),
            new ConsoleSanitizer()
        );
    }
}
