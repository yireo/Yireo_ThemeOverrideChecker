<?php

declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use DOMDocument;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\Design\Theme\ThemePackageList;

class ThemeProvider
{
    private ThemePackageList $themePackageList;

    public function __construct(
        ThemePackageList $themePackageList,
    ) {
        $this->themePackageList = $themePackageList;
    }

    /**
     * @param ThemePackage $theme
     * @return string
     */
    public function getThemePath(ThemePackage $theme): string
    {
        return $theme->getPath();
    }

    /**
     * @param string $themeName
     * @return ThemePackage
     */
    public function getTheme(string $themeName): ThemePackage
    {
        if (!preg_match('#^frontend/#', $themeName)) {
            $themeName = 'frontend/'.$themeName;
        }

        return $this->themePackageList->getTheme($themeName);
    }

    /**
     * @param ThemePackage $theme
     * @return ThemePackage
     * @throws NotFoundException
     */
    public function getParentThemeFromTheme(ThemePackage $theme): ThemePackage
    {
        $themePath = $theme->getPath();
        $themeConfigFile = $themePath.'/theme.xml';
        $dom = new DOMDocument();
        $dom->load($themeConfigFile);
        $themeNode = $dom->getElementsByTagName('theme')->item(0);
        $themeParentNode = $themeNode->getElementsByTagName('parent')->item(0);
        if ($themeParentNode === null) {
            throw new NotFoundException(__('Theme has no parent'));
        }

        $parentThemeName = (string)$themeParentNode->nodeValue;

        return $this->getTheme($parentThemeName);
    }
}
