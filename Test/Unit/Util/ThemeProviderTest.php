<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\Design\Theme\ThemePackageList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class ThemeProviderTest extends TestCase
{
    private string $themeDir = '';

    protected function setUp(): void
    {
        $this->themeDir = sys_get_temp_dir() . '/theme-provider-' . uniqid();
        mkdir($this->themeDir);
    }

    protected function tearDown(): void
    {
        @unlink($this->themeDir . '/theme.xml');
        rmdir($this->themeDir);
    }

    public function testGetParentThemeFromTheme(): void
    {
        file_put_contents(
            $this->themeDir . '/theme.xml',
            '<theme><title>Child</title><parent>Magento/blank</parent></theme>'
        );
        $parentTheme = new ThemePackage('frontend/Magento/blank', '/var/www/blank');

        $themePackageList = $this->createMock(ThemePackageList::class);
        $themePackageList->expects($this->once())->method('getTheme')
            ->with('frontend/Magento/blank')
            ->willReturn($parentTheme);

        $this->assertSame($parentTheme, (new ThemeProvider($themePackageList))->getParentThemeFromTheme($this->getTheme()));
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function getInvalidThemeXml(): array
    {
        return [
            'missing file' => [null],
            'malformed XML' => ['<theme><parent>Magento/blank</theme'],
            'no theme node' => ['<config><parent>Magento/blank</parent></config>'],
            'no parent node' => ['<theme><title>Child</title></theme>'],
        ];
    }

    #[DataProvider('getInvalidThemeXml')]
    public function testGetParentThemeFromThemeWithInvalidThemeXml(?string $themeXml): void
    {
        if ($themeXml !== null) {
            file_put_contents($this->themeDir . '/theme.xml', $themeXml);
        }

        $this->expectException(NotFoundException::class);
        (new ThemeProvider($this->createStub(ThemePackageList::class)))->getParentThemeFromTheme($this->getTheme());
    }

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

    private function getTheme(): ThemePackage
    {
        return new ThemePackage('frontend/Yireo/child', $this->themeDir);
    }
}
