<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Functional\Util;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;

class ThemeFileResolverTest extends TestCase
{
    public function testThemeFileResolver()
    {
        $themeFileResolver = ObjectManager::getInstance()->get(ThemeFileResolver::class);
        $this->assertInstanceOf(ThemeFileResolver::class, $themeFileResolver);
    }
}
