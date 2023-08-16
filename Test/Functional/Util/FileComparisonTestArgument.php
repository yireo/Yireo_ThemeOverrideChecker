<?php
declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Test\Functional\Util;

class FileComparisonTestArgument
{
    public function __construct(
        public string $file1Contents,
        public string $file2Contents,
        public int $lineDifference,
        public int $lineCountDifference,
        public int $percentageDifference
    ) {
    }
}
