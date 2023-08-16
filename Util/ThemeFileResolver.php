<?php

declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\FileSystem as ViewFilesystem;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Exception\ThemeFileResolveException;

class ThemeFileResolver
{
    private ViewFilesystem $viewFilesystem;
    private ComponentRegistrar $componentRegistrar;
    private SplFileInfoFactory $splFileInfoFactory;
    private ThemeProvider $themeProvider;
    private array $resolverTrace = [];
    private DirectoryList $directoryList;

    public function __construct(
        ViewFilesystem $viewFilesystem,
        ComponentRegistrar $componentRegistrar,
        SplFileInfoFactory $splFileInfoFactory,
        ThemeProvider $themeProvider,
        DirectoryList $directoryList
    ) {
        $this->viewFilesystem = $viewFilesystem;
        $this->componentRegistrar = $componentRegistrar;
        $this->splFileInfoFactory = $splFileInfoFactory;
        $this->themeProvider = $themeProvider;
        $this->directoryList = $directoryList;
    }

    /**
     * @param SplFileInfo $themeFile
     * @param ThemePackage $theme
     * @return SplFileInfo
     * @throws ThemeFileResolveException
     */
    public function resolveOriginalFile(SplFileInfo $themeFile, ThemePackage $theme): SplFileInfo
    {
        $this->resolverTrace = [];

        try {
            return $this->resolveOriginalFileFromParentTheme($themeFile, $theme);
        } catch (ThemeFileResolveException $e) {
        }

        try {
            return $this->resolveOriginalFileFromModule($themeFile);
        } catch (ThemeFileResolveException $e) {
        }

        try {
            return $this->resolveOriginalFileFromLib($themeFile);
        } catch (ThemeFileResolveException $e) {
        }

        $parentThemeFile = $this->viewFilesystem->getFilename($themeFile->getRelativePathname());
        if ($parentThemeFile !== false && file_exists($parentThemeFile)) {
            return $this->getSplInfoFile($parentThemeFile);
        }

        throw new ThemeFileResolveException(
            __(
                'Parent theme file for "'.$themeFile->getRelativePathname().'" not found. Tried: '
                ."\n- "
                .implode("\n- ", $this->resolverTrace)
            )
        );
    }

    /**
     * @param SplFileInfo $themeFile
     * @param ThemePackage $theme
     * @return SplFileInfo
     * @throws ThemeFileResolveException
     */
    public function resolveOriginalFileFromParentTheme(SplFileInfo $themeFile, ThemePackage $theme): SplFileInfo
    {
        try {
            $parentTheme = $this->themeProvider->getParentThemeFromTheme($theme);
        } catch (NotFoundException $e) {
            throw new ThemeFileResolveException(__('Unable to determine parent theme'));
        }

        $parentThemeFile = $parentTheme->getPath().'/'.$themeFile->getRelativePathname();
        $this->resolverTrace[] = $parentThemeFile;

        if (file_exists($parentThemeFile)) {
            return $this->getSplInfoFile($parentThemeFile);
        }

        throw new ThemeFileResolveException(__('Parent theme file for "'.$themeFile->getRelativePathname().'" not found'));
    }

    /**
     * @param SplFileInfo $themeFile
     * @return SplFileInfo
     * @throws ThemeFileResolveException
     */
    public function resolveOriginalFileFromModule(SplFileInfo $themeFile): SplFileInfo
    {
        $themeFilePath = $themeFile->getRelativePathname();
        if (!preg_match('#^([a-zA-Z0-9]+)_([a-zA-Z0-9]+)/(.*)#', $themeFilePath, $match)) {
            throw new ThemeFileResolveException(__('File "'.$themeFilePath.'" does not look like a module file'));
        }

        $moduleName = $match[1].'_'.$match[2];
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $moduleFile = $modulePath.'/view/frontend/'.$match[3];
        $this->resolverTrace[] = $moduleFile;

        if (file_exists($moduleFile)) {
            return $this->getSplInfoFile($moduleFile);
        }

        $moduleFile = $modulePath.'/view/base/'.$match[3];
        $this->resolverTrace[] = $moduleFile;
        if (file_exists($moduleFile)) {
            return $this->getSplInfoFile($moduleFile);
        }

        throw new ThemeFileResolveException(__('Module file for "'.$themeFilePath.'" not found'));
    }

    /**
     * @param SplFileInfo $themeFile
     * @return SplFileInfo
     * @throws ThemeFileResolveException
     */
    public function resolveOriginalFileFromLib(SplFileInfo $themeFile): SplFileInfo
    {
        $themeFilePath = $themeFile->getRelativePathname();
        if (false === preg_match('/^web/', $themeFilePath)) {
            throw new ThemeFileResolveException(__('File for "'.$themeFilePath.'" is not a lib file'));
        }

        $libFile = $this->directoryList->getRoot() . '/lib/' . $themeFilePath;
        $this->resolverTrace[] = $libFile;
        if (file_exists($libFile)) {
            return $this->getSplInfoFile($libFile);
        }

        throw new ThemeFileResolveException(__('File for "'.$themeFilePath.'" is not a lib file'));
    }

    /**
     * @param string $file
     * @return SplFileInfo
     */
    private function getSplInfoFile(string $file): SplFileInfo
    {
        return $this->splFileInfoFactory->create($file);
    }
}
