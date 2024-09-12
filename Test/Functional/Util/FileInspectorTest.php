<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Functional\Util;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Yireo\ThemeOverrideChecker\Util\FileInspector;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;

class FileInspectorTest extends TestCase
{
    public function testFileInspection()
    {
        $testFile = __FILE__;
        $splFileInfoBuilder = $this->get(SplFileInfoBuilder::class);
        $fileInspector = $this->create(FileInspector::class, ['file' => $splFileInfoBuilder->create($testFile)]);
        $this->assertInstanceOf(FileInspector::class, $fileInspector);
        $this->assertNotEmpty($fileInspector->getContents());
        $this->assertLineCountHigher(25, $fileInspector->getLineCount(), $testFile);

        $reflection = new ReflectionClass(FileInspector::class);
        $testFile = $reflection->getFileName();
        $fileInspector = $this->create(FileInspector::class, ['file' => $splFileInfoBuilder->create($testFile)]);
        $this->assertInstanceOf(FileInspector::class, $fileInspector);
        $this->assertNotEmpty($fileInspector->getContents());
        $this->assertLineCountHigher(30, $fileInspector->getLineCount(), $testFile);
    }

    private function assertLineCountHigher($expected, $actual, string $file)
    {
        $this->assertTrue($actual > $expected, $file.' has '.$actual.' lines');
    }

    private function get(string $className, array $arguments = []): object
    {
        return ObjectManager::getInstance()->get($className, $arguments);
    }

    private function create(string $className, array $arguments = []): object
    {
        return ObjectManager::getInstance()->create($className, $arguments);
    }
}
