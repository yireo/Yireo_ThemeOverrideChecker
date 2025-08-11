<?php declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;
use Yireo\ThemeOverrideChecker\Util\FileComparison;
use Yireo\ThemeOverrideChecker\Util\SplFileInfoBuilder;
use Yireo\ThemeOverrideChecker\Util\ThemeFileResolver;
use Yireo\ThemeOverrideChecker\Util\ThemeProvider;

class DiffOverrideCommand extends Command
{
    private ThemeFileResolver $themeFileResolver;
    private ThemeProvider $themeProvider;
    private FileComparison $fileComparison;
    private SplFileInfoBuilder $splFileInfoBuilder;
    private State $appState;

    public function __construct(
        ThemeFileResolver $themeFileResolver,
        ThemeProvider $themeProvider,
        FileComparison $fileComparison,
        SplFileInfoBuilder $splFileInfoBuilder,
        State $appState,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->themeFileResolver = $themeFileResolver;
        $this->themeProvider = $themeProvider;
        $this->fileComparison = $fileComparison;
        $this->splFileInfoBuilder = $splFileInfoBuilder;
        $this->appState = $appState;
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('yireo:theme-overrides:diff')
            ->setDescription('Show the diff betweenspecific file and its parent file')
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

        /** @var SplFileInfo $file */
        $file = $this->splFileInfoBuilder->create($themePath . '/' .$fileName, $themePath);
        if (false === $file->getRealPath()) {
            $output->writeln('<error>Unable to read file</error>');
            return Command::FAILURE;
        }

        $this->appState->setAreaCode('frontend');
        $parentFile = $this->themeFileResolver->resolveOriginalFile($file, $theme);
        $diff = $this->fileComparison->getDiff($file, $parentFile);

        $diffLines = explode("\n", $diff);
        foreach ($diffLines as $diffLine) {
            if (preg_match('/^\+/', $diffLine)) {
                $output->writeln('<info>'.$diffLine.'</info>');
                continue;
            }

            if (preg_match('/^\-/', $diffLine)) {
                $output->writeln('<comment>'.$diffLine.'</comment>');
                continue;
            }

            $output->writeln($diffLine);
        }

        return Command::SUCCESS;
    }
}
