<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ThemeOverrideChecker\Exception\ThemeFileResolveException;
use Yireo\ThemeOverrideChecker\Util\PathGuard;

class PathGuardTest extends TestCase
{
    private string $baseDir = '';

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/path-guard-' . uniqid();
        mkdir($this->baseDir . '/theme/templates', 0777, true);
        file_put_contents($this->baseDir . '/theme/templates/file.phtml', 'theme');
        file_put_contents($this->baseDir . '/secret.php', 'secret');
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
    public static function getUnsafeRelativePaths(): array
    {
        return [
            'parent traversal' => ['../secret.php'],
            'nested traversal' => ['templates/../../secret.php'],
            'module traversal' => ['Magento_Theme/../../../../app/etc/env.php'],
            'absolute path' => ['/etc/passwd'],
            'null byte' => ["templates/file.phtml\0.txt"],
            'backslash traversal' => ['templates\\..\\..\\secret.php'],
            'empty path' => [''],
        ];
    }

    #[DataProvider('getUnsafeRelativePaths')]
    public function testAssertSafeRelativePathRejectsUnsafePaths(string $relativePath): void
    {
        $this->expectException(ThemeFileResolveException::class);
        (new PathGuard())->assertSafeRelativePath($relativePath);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function getSafeRelativePaths(): array
    {
        return [
            'template' => ['Magento_Theme/templates/html/header.phtml'],
            'web file' => ['web/css/source/_extend.less'],
            'dotted file name' => ['web/js/file..min.js'],
            'hidden file' => ['etc/.view.xml'],
        ];
    }

    #[DataProvider('getSafeRelativePaths')]
    public function testAssertSafeRelativePathAcceptsSafePaths(string $relativePath): void
    {
        (new PathGuard())->assertSafeRelativePath($relativePath);
        $this->addToAssertionCount(1);
    }

    public function testIsInside(): void
    {
        $pathGuard = new PathGuard();
        $themeDir = $this->baseDir . '/theme';

        $this->assertTrue($pathGuard->isInside($themeDir . '/templates/file.phtml', $themeDir));
        $this->assertFalse($pathGuard->isInside($themeDir . '/../secret.php', $themeDir));
        $this->assertFalse($pathGuard->isInside($themeDir . '/templates/missing.phtml', $themeDir));
        $this->assertFalse($pathGuard->isInside($themeDir, $themeDir));
        $this->assertFalse($pathGuard->isInside($themeDir . '/templates/file.phtml', $this->baseDir . '/missing'));
        $this->assertFalse($pathGuard->isInside($this->baseDir . '/secret.php', $this->baseDir . '/the'));
    }

    public function testIsInsideRejectsSymlinksPointingOutside(): void
    {
        $themeDir = $this->baseDir . '/theme';
        symlink($this->baseDir . '/secret.php', $themeDir . '/templates/link.phtml');

        $this->assertFalse((new PathGuard())->isInside($themeDir . '/templates/link.phtml', $themeDir));
    }
}
