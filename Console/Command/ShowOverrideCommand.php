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
use Symfony\Component\Finder\Finder;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoFactory;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class ShowOverrideCommand extends Command
{
    private ThemeFileResolver $themeFileResolver;
    private ThemeProvider $themeProvider;
    private FileComparison $fileComparison;
    private SplFileInfoFactory $splFileInfoFactory;
    private State $appState;

    public function __construct(
        Finder $finder,
        ThemeFileResolver $themeFileResolver,
        ThemeProvider $themeProvider,
        FileComparison $fileComparison,
        SplFileInfoFactory $splFileInfoFactory,
        State $appState,
        string $name = null
    ) {
        parent::__construct($name);
        $this->finder = $finder;
        $this->themeFileResolver = $themeFileResolver;
        $this->themeProvider = $themeProvider;
        $this->fileComparison = $fileComparison;
        $this->splFileInfoFactory = $splFileInfoFactory;
        $this->appState = $appState;
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
     * @return void
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

        $file = $this->splFileInfoFactory->create($themePath . '/' .$fileName, $themePath);

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

        $table->render();
        return Command::SUCCESS;
    }
}
