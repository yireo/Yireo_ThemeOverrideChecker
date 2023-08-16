<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Functional\Util;

use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\FileInspector;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoFactory;

class FileComparisonTest extends TestCase
{
    /**
     * @dataProvider getFileComparisonTestArguments()
     * @return void
     */
    public function testFileComparison(
        FileComparisonTestArgument $argument
    ) {
        $file1 = $this->getMockBuilder(SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file1->method('getContents')->willReturn($argument->file1Contents);

        $file2 = $this->getMockBuilder(SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file2->method('getContents')->willReturn($argument->file2Contents);

        /** @var FileComparison $fileComparison */
        $fileComparison = $this->get(FileComparison::class);
        $this->assertEquals(
            $argument->lineDifference,
            $fileComparison->getLineDifference($file1, $file2),
            "Unexpected line difference: \nFile 1:\n$argument->file1Contents\n\nFile 2:\n$argument->file2Contents"
        );

        $this->assertEquals(
            $argument->lineCountDifference,
            $fileComparison->getLineCountDifference($file1, $file2),
            "Unexpected line count difference: \nFile 1:\n$argument->file1Contents\n\nFile 2:\n$argument->file2Contents"
        );

        $this->assertEquals(
            $argument->percentageDifference,
            $fileComparison->getPercentageDifference($file1, $file2),
            "Unexpected percentage difference: \nFile 1:\n$argument->file1Contents\n\nFile 2:\n$argument->file2Contents"
        );
    }

    /**
     * @return FileComparisonTestArgument[][]
     */
    public function getFileComparisonTestArguments(): array
    {
        return [
            [new FileComparisonTestArgument(
                "foobar1",
                "foobar1",
                0,
                0,
                0,
            )],
            [new FileComparisonTestArgument(
                "foobar2\nfoobar2",
                "foobar2",
                0,
                1,
                50,
            )],
            [new FileComparisonTestArgument(
                "// test\nfoobar3",
                "// test something else\nfoobar3",
                0,
                0,
                0,
            )],
            [new FileComparisonTestArgument(
                "foobar4\nfoobar4a",
                "foobar4",
                1,
                1,
                50,
            )],
        ];
    }

    /**
     * @param string $className
     * @return object
     */
    private function get(string $className): object
    {
        return ObjectManager::getInstance()->get($className);
    }
}
