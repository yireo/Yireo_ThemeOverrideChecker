<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\FileSystem as ViewFilesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Exception\ThemeFileResolveException;
use Yireo\ThemeOverrideChecker\Util\PathGuard;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class ThemeFileResolverTest extends TestCase
{
    private string $rootDir = '';
    private ViewFilesystem $viewFilesystem;
    private ComponentRegistrar $componentRegistrar;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/theme-file-resolver-' . uniqid();
        $this->createFile('app/design/frontend/Yireo/child/Magento_Theme/templates/a.phtml');
        $this->createFile('app/design/frontend/Yireo/child/web/css/a.less');
        $this->createFile('app/design/frontend/Yireo/parent/Magento_Theme/templates/a.phtml');
        $this->createFile('module/view/frontend/templates/b.phtml');
        $this->createFile('lib/web/css/a.less');
        $this->createFile('app/etc/env.php');

        $this->viewFilesystem = $this->createStub(ViewFilesystem::class);
        $this->componentRegistrar = $this->createStub(ComponentRegistrar::class);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->rootDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->rootDir);
    }

    public function testResolveOriginalFileFromParentTheme(): void
    {
        $resolved = $this->getResolver()->resolveOriginalFile(
            $this->getThemeFile('Magento_Theme/templates/a.phtml'),
            $this->getTheme()
        );

        $this->assertSame(
            $this->rootDir . '/app/design/frontend/Yireo/parent/Magento_Theme/templates/a.phtml',
            $resolved->getPathname()
        );
    }

    public function testResolveOriginalFileRejectsPathTraversal(): void
    {
        $viewFilesystem = $this->createMock(ViewFilesystem::class);
        $viewFilesystem->expects($this->never())->method('getFilename');
        $this->viewFilesystem = $viewFilesystem;

        $this->expectException(ThemeFileResolveException::class);
        $this->getResolver()->resolveOriginalFile(
            $this->getThemeFile('Magento_Theme/../../../../../../app/etc/env.php'),
            $this->getTheme()
        );
    }

    public function testResolveOriginalFileFromModuleRejectsPathTraversal(): void
    {
        $componentRegistrar = $this->createStub(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')->willReturn($this->rootDir . '/module');
        $this->componentRegistrar = $componentRegistrar;

        $this->expectException(ThemeFileResolveException::class);
        $this->getResolver()->resolveOriginalFileFromModule(
            $this->getThemeFile('Yireo_Example/../../app/etc/env.php')
        );
    }

    public function testResolveOriginalFileFromParentThemeIgnoresSymlinkOutsideParentTheme(): void
    {
        $parentFile = $this->rootDir . '/app/design/frontend/Yireo/parent/Magento_Theme/templates/a.phtml';
        unlink($parentFile);
        symlink($this->rootDir . '/app/etc/env.php', $parentFile);

        $this->expectException(ThemeFileResolveException::class);
        $this->getResolver()->resolveOriginalFileFromParentTheme(
            $this->getThemeFile('Magento_Theme/templates/a.phtml'),
            $this->getTheme()
        );
    }

    private function getResolver(): ThemeFileResolver
    {
        $splFileInfoBuilder = $this->createStub(SplFileInfoBuilder::class);
        $splFileInfoBuilder->method('create')->willReturnCallback(
            fn (string $file) => new SplFileInfo($file, '', basename($file))
        );

        $themeProvider = $this->createStub(ThemeProvider::class);
        $themeProvider->method('getParentThemeFromTheme')->willReturn(
            new ThemePackage('frontend/Yireo/parent', $this->rootDir . '/app/design/frontend/Yireo/parent')
        );

        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn($this->rootDir);

        return new ThemeFileResolver(
            $this->viewFilesystem,
            $this->componentRegistrar,
            $splFileInfoBuilder,
            $themeProvider,
            $directoryList,
            new PathGuard()
        );
    }

    private function getTheme(): ThemePackage
    {
        return new ThemePackage('frontend/Yireo/child', $this->rootDir . '/app/design/frontend/Yireo/child');
    }

    private function getThemeFile(string $relativePathname): SplFileInfo
    {
        $themePath = $this->rootDir . '/app/design/frontend/Yireo/child';

        return new SplFileInfo($themePath . '/' . $relativePathname, dirname($relativePathname), $relativePathname);
    }

    private function createFile(string $relativePath): void
    {
        $file = $this->rootDir . '/' . $relativePath;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, $relativePath);
    }
}
