<?php
declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Differ;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;

class DifferFactory
{
    /**
     * @return Differ
     */
    public function create(array $options = []): Differ
    {
        $options = array_merge([
            'collapseRanges' => true,
            'commonLineThreshold' => 6,
            'contextLines' => 3,
            'fromFile' => '',
            'fromFileDate' => null,
            'toFile' => '',
            'toFileDate' => null,
        ], $options);
        
        $builder = new StrictUnifiedDiffOutputBuilder($options);
        return new Differ($builder);
    }
}
