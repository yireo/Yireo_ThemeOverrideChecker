<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\View\Design\Theme\ThemePackage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Finder\Finder;
use Yireo\ThemeOverrideChecker\Console\Command\CheckOverrideCommand;
use Yireo\ThemeOverrideChecker\Exception\ThemeFileResolveException;
use Yireo\ThemeOverrideChecker\Util\ConsoleSanitizer;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\OverrideAdviser;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class CheckOverrideCommandTest extends TestCase
{
    private string $baseDir = '';

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/check-override-' . uniqid();
        mkdir($this->baseDir . '/theme/templates', 0777, true);
        file_put_contents($this->baseDir . '/theme/templates/file.phtml', 'theme');
        file_put_contents($this->baseDir . '/secret.php', 'secret');
        symlink($this->baseDir . '/secret.php', $this->baseDir . '/theme/templates/link.phtml');
    }

    protected function tearDown(): void
    {
        @unlink($this->baseDir . '/theme/templates/link.phtml');
        @unlink($this->baseDir . '/theme/templates/file.phtml');
        @unlink($this->baseDir . '/secret.php');
        @rmdir($this->baseDir . '/theme/templates');
        @rmdir($this->baseDir . '/theme');
        @rmdir($this->baseDir);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function getInvalidExtensions(): array
    {
        return [
            'glob' => ['*'],
            'brace expansion' => ['{php,phtml}'],
            'path' => ['../phtml'],
            'too long' => ['abcdefghijk'],
        ];
    }

    #[DataProvider('getInvalidExtensions')]
    public function testRejectsInvalidExtension(string $extension): void
    {
        $themeProvider = $this->createMock(ThemeProvider::class);
        $themeProvider->expects($this->never())->method('getTheme');

        $commandTester = new CommandTester($this->getCommand($themeProvider));
        $commandTester->execute(['theme' => 'Yireo/child', '--extension' => $extension]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid extension', $commandTester->getDisplay());
    }

    public function testSkipsSymlinks(): void
    {
        $commandTester = new CommandTester($this->getCommand());
        $commandTester->execute(['theme' => 'Yireo/child', '--extension' => 'phtml']);

        $display = $commandTester->getDisplay();
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('templates/file.phtml', $display);
        $this->assertStringNotContainsString('link.phtml', $display);
    }

    private function getCommand(?ThemeProvider $themeProvider = null): CheckOverrideCommand
    {
        if ($themeProvider === null) {
            $themeProvider = $this->createStub(ThemeProvider::class);
            $themeProvider->method('getTheme')->willReturn(
                new ThemePackage('frontend/Yireo/child', $this->baseDir . '/theme')
            );
            $themeProvider->method('getThemePath')->willReturn($this->baseDir . '/theme');
            $themeProvider->method('getParentThemeFromTheme')->willReturn(
                new ThemePackage('frontend/Yireo/parent', $this->baseDir . '/parent')
            );
        }

        $themeFileResolver = $this->createStub(ThemeFileResolver::class);
        $themeFileResolver->method('resolveOriginalFile')->willThrowException(
            new ThemeFileResolveException(__('Not found'))
        );

        $overrideAdviser = $this->createStub(OverrideAdviser::class);
        $overrideAdviser->method('advise')->willReturn('Advice');

        return new CheckOverrideCommand(
            new Finder(),
            $themeFileResolver,
            $themeProvider,
            $this->createStub(FileComparison::class),
            $this->createStub(State::class),
            $overrideAdviser,
            new ConsoleSanitizer()
        );
    }
}
