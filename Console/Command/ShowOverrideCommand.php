<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\FileInspectorFactory;
use Yireo\ThemeOverrideChecker\Util\OverrideAdviser;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class ShowOverrideCommand extends Command
{
    private ThemeFileResolver $themeFileResolver;
    private ThemeProvider $themeProvider;
    private FileComparison $fileComparison;
    private SplFileInfoBuilder $splFileInfoBuilder;
    private State $appState;
    private OverrideAdviser $overrideAdviser;
    private FileInspectorFactory $fileInspectorFactory;

    public function __construct(
        ThemeFileResolver $themeFileResolver,
        ThemeProvider $themeProvider,
        FileComparison $fileComparison,
        SplFileInfoBuilder $splFileInfoBuilder,
        State $appState,
        OverrideAdviser $overrideAdviser,
        FileInspectorFactory $fileInspectorFactory,
        string $name = null
    ) {
        parent::__construct($name);
        $this->themeFileResolver = $themeFileResolver;
        $this->themeProvider = $themeProvider;
        $this->fileComparison = $fileComparison;
        $this->splFileInfoBuilder = $splFileInfoBuilder;
        $this->appState = $appState;
        $this->overrideAdviser = $overrideAdviser;
        $this->fileInspectorFactory = $fileInspectorFactory;
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('yireo:theme-overrides:show')
            ->setDescription('Show the overrides of a specified theme and specific file')
            ->addArgument('theme', InputOption::VALUE_REQUIRED, 'Theme name')
            ->addArgument('file', InputOption::VALUE_REQUIRED, 'Theme file');
    }

    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $themeName = $input->getArgument('theme');
        if (empty($themeName)) {
            $output->writeln('<error>No theme argument given</error>');

            return Command::FAILURE;
        }

        $fileName = $input->getArgument('file');
        if (empty($fileName)) {
            $output->writeln('<error>No file argument given</error>');

            return Command::FAILURE;
        }

        $theme = $this->themeProvider->getTheme($themeName);

        try {
            $themePath = $this->themeProvider->getThemePath($theme);
        } catch (NotFoundException|LocalizedException $e) {
            $output->writeln('<error>No theme path found for theme "'.$themeName.'"</error>');

            return Command::FAILURE;
        }

        try {
            $parentTheme = $this->themeProvider->getParentThemeFromTheme($theme);
        } catch (NotFoundException $e) {
            $parentTheme = false;
        }

        /** @var SplFileInfo $file */
        $file = $this->splFileInfoBuilder->create($themePath . '/' .$fileName, $themePath);

        $table = new Table($output);
        $table->setHeaders([
            'Check',
            'Value',
        ]);

        $table->addRow([
            'Theme name',
            $themeName,
        ]);

        $table->addRow([
            'Theme path',
            $themePath,
        ]);

        $table->addRow([
            'Theme file name',
            !empty($file->getRealPath()) ? $file->getRealPath() : 'n/a'
        ]);

        $themeFileInspector = $this->fileInspectorFactory->create(['file' => $file]);
        $table->addRow([
            'Theme file line count',
            $themeFileInspector->getLineCount(),
        ]);

        $table->addRow([
            'Parent theme name',
            $parentTheme ? $parentTheme->getVendor() . '/' . $parentTheme->getName() : 'n/a'
        ]);

        $table->addRow([
            'Parent theme path',
            $parentTheme ? $parentTheme->getPath() : 'n/a'
        ]);

        $this->appState->setAreaCode('frontend');
        $parentFile = $this->themeFileResolver->resolveOriginalFile($file, $theme);
        $table->addRow([
            'Original file',
            $parentFile,
        ]);

        $parentThemeFileInspector = $this->fileInspectorFactory->create(['file' => $parentFile]);
        $table->addRow([
            'Original file line count',
            $parentThemeFileInspector->getLineCount(),
        ]);

        $lineCountDifference = $this->fileComparison->getLineCountDifference($file, $parentFile);
        $table->addRow([
            'Total line count difference',
            $lineCountDifference
        ]);

        $lineDifference = $this->fileComparison->getLineDifference($file, $parentFile);
        $table->addRow([
            'Lines found to be different',
            $lineDifference
        ]);

        $advice = $this->overrideAdviser->advise($file, $parentFile, $lineDifference, $lineCountDifference);
        $table->addRow([
            'Advice',
            $advice
        ]);

        $table->render();
        return Command::SUCCESS;
    }
}
