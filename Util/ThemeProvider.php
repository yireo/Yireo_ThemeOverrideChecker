<?php

declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Util;

use DOMDocument;
use DOMElement;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\Design\Theme\ThemePackageList;

class ThemeProvider
{
    private ThemePackageList $themePackageList;

    public function __construct(
        ThemePackageList $themePackageList
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
     * @throws NotFoundException
     */
    public function getTheme(string $themeName): ThemePackage
    {
        if (!preg_match('#^frontend/#', $themeName)) {
            $themeName = 'frontend/'.$themeName;
        }

        try {
            return $this->themePackageList->getTheme($themeName);
        } catch (\UnexpectedValueException $e) {
            throw new NotFoundException(__('Theme "%1" not found', $themeName));
        }
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
        if (!is_file($themeConfigFile)) {
            throw new NotFoundException(__('Theme configuration file "%1" not found', $themeConfigFile));
        }

        $dom = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->load($themeConfigFile, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if (!$loaded) {
            throw new NotFoundException(__('Theme configuration file "%1" could not be parsed', $themeConfigFile));
        }

        $themeNode = $dom->getElementsByTagName('theme')->item(0);
        if (!$themeNode instanceof DOMElement) {
            throw new NotFoundException(__('Theme configuration file "%1" has no theme node', $themeConfigFile));
        }

        $themeParentNode = $themeNode->getElementsByTagName('parent')->item(0);
        if ($themeParentNode === null) {
            throw new NotFoundException(__('Theme has no parent'));
        }

        $parentThemeName = (string)$themeParentNode->nodeValue;

        return $this->getTheme($parentThemeName);
    }
}
