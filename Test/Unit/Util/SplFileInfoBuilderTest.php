<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Unit\Util;

use Magento\Framework\App\Filesystem\DirectoryList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;

class SplFileInfoBuilderTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string, string}>
     */
    public static function getFiles(): array
    {
        return [
            'file in base path' => ['/var/www/theme/templates/a.phtml', '/var/www/theme', 'templates/a.phtml'],
            'base path with trailing slash' => ['/var/www/theme/templates/a.phtml', '/var/www/theme/', 'templates/a.phtml'],
            'base path repeated in file' => ['/var/www/theme/var/www/theme/a.phtml', '/var/www/theme', 'var/www/theme/a.phtml'],
            'base path not a prefix' => ['/srv/var/www/theme/a.phtml', '/var/www/theme', 'srv/var/www/theme/a.phtml'],
            'base path is a name prefix only' => ['/var/www/theme2/a.phtml', '/var/www/theme', 'var/www/theme2/a.phtml'],
            'default root' => ['/var/www/html/lib/web/a.js', null, 'lib/web/a.js'],
        ];
    }

    #[DataProvider('getFiles')]
    public function testCreate(string $file, ?string $relativePath, string $expectedRelativePathname): void
    {
        $directoryList = $this->createStub(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/var/www/html');

        $splFileInfo = (new SplFileInfoBuilder($directoryList))->create($file, $relativePath);

        $this->assertSame($file, $splFileInfo->getPathname());
        $this->assertSame($expectedRelativePathname, $splFileInfo->getRelativePathname());
    }
}
