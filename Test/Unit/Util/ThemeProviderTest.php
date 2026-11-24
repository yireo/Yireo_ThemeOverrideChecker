<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Design\Theme\ThemePackageList;
use PHPUnit\Framework\TestCase;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class ThemeProviderTest extends TestCase
{
    public function testGetThemeThrowsNotFoundExceptionForUnknownTheme(): void
    {
        $themePackageList = $this->createStub(ThemePackageList::class);
        $themePackageList->method('getTheme')->willThrowException(
            new \UnexpectedValueException("No theme registered with name 'frontend/Yireo/unknown'")
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Theme "frontend/Yireo/unknown" not found');
        (new ThemeProvider($themePackageList))->getTheme('Yireo/unknown');
    }
}
