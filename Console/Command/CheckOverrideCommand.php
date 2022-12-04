<?php

declare(strict_types=1);

namespace Yireo\ThemeOverrideChecker\Console\Command;

use DOMDocument;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Design\Theme\ListInterface as ThemeListInterface;
use Magento\Framework\View\Design\Theme\ThemeList;
use Magento\Framework\View\Design\Theme\ThemePackage;
use Magento\Framework\View\Design\Theme\ThemePackageList;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\FileSystem as ViewFilesystem;
use Yireo\ThemeOverrideChecker\Differ\DifferFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CheckOverrideCommand extends Command
{
    private ComponentRegistrar $componentRegistrar;
    private ThemeProviderInterface $themeProvider;
    private AppState $appState;
    private ThemeListInterface $themeList;
    private ThemePackageList $themePackageList;
    private Finder $finder;
    private DifferFactory $differFactory;
    private ViewFilesystem $viewFilesystem;
    private AssetRepository $assetRepository;
    private DirectoryList $directoryList;
    
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        ThemeProviderInterface $themeProvider,
        AppState $appState,
        ThemeListInterface $themeList,
        ThemePackageList $themePackageList,
        Finder $finder,
        DifferFactory $differFactory,
        ViewFilesystem $viewFilesystem,
        AssetRepository $assetRepository,
        DirectoryList $directoryList,
        string $name = null
    ) {
        parent::__construct($name);
        $this->componentRegistrar = $componentRegistrar;
        $this->themeProvider = $themeProvider;
        $this->appState = $appState;
        $this->themeList = $themeList;
        $this->themePackageList = $themePackageList;
        $this->finder = $finder;
        $this->differFactory = $differFactory;
        $this->viewFilesystem = $viewFilesystem;
        $this->assetRepository = $assetRepository;
        $this->directoryList = $directoryList;
    }
    
    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('yireo:check-theme-overrides')
            ->setDescription('Check the overrides of a specified theme')
            ->addArgument('theme', InputOption::VALUE_REQUIRED, 'Theme name')
            ->addOption('diff',null,  InputOption::VALUE_OPTIONAL, 'Show diff');
    }
    
    /**
     * CLI command description.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $themeName = $input->getArgument('theme');
        if (empty($themeName)) {
            $output->writeln('<error>No theme argument given</error>');
            return;
        }
        
        if (!preg_match('#^frontend/#', $themeName)) {
            $themeName = 'frontend/' . $themeName;
        }
        
        $this->appState->setAreaCode(Area::AREA_FRONTEND);
        $themePath = $this->componentRegistrar->getPath(ComponentRegistrar::THEME, $themeName);
        if (empty($themePath)) {
            $output->writeln('<error>No theme path found for theme "' . $themeName . '"</error>');
            return;
        }
        
        $output->writeln('Theme path found: ' . $themePath);
        $showDiff = (bool) $input->getOption('diff');
        
        $theme = $this->themePackageList->getTheme($themeName);
        $parentThemeName = $this->getParentThemeFromPath($theme->getPath());
        $parentTheme = $this->themePackageList->getTheme('frontend/' . $parentThemeName);
        if ($parentTheme) {
            $output->writeln('Theme parent found: ' . $parentTheme->getPath());
        } else {
            $output->writeln('<error>No theme parent found</error>');
            return;
        }
        
        $table = new Table($output);
        $table->setHeaders([
            'Theme file',
            'Original file',
            'Number of different lines'
        ]);
    
        $themeFiles = $this->finder->in($themePath)->files();
        foreach ($themeFiles as $themeFile) {
            $themeFileContents = $themeFile->getContents();
            try {
                $parentThemeFile = $this->resolveParentThemeFile($themeFile, $parentTheme);
                $parentThemeRelativeFile = str_replace($this->directoryList->getRoot().'/', '', $parentThemeFile);
                $parentThemeFileContents = file_get_contents($parentThemeFile);
                $differBuilderOptions = ['toFile' => $themeFile->getRealPath(), 'fromFile' => $parentThemeRelativeFile];
                $differ = $this->differFactory->create($differBuilderOptions);
                $diff = $differ->diff($parentThemeFileContents, $themeFileContents);
            } catch (Exception $e) {
                $parentThemeFile = 'UNKNOWN';
                $diff = 'ERROR: ' . $e->getMessage();
            }
            
            $table->addRow([
                $themeFile->getRelativePathname(),
                $parentThemeRelativeFile,
                $diff ? 'Found differences' : 'No difference',
            ]);
        }
        
        $table->render();
        
        if ($showDiff) {
            $output->writeln($diff);
            $output->writeln("\n\n");
        }
    }
    
    /**
     * @param SplFileInfo $themeFile
     * @param ThemePackage $parentTheme
     * @return string
     * @throws Exception
     */
    private function resolveParentThemeFile(SplFileInfo $themeFile, ThemePackage $parentTheme): string
    {
        $parentThemeFile = $parentTheme->getPath() . '/' . $themeFile->getRelativePathname();
        if (file_exists($parentThemeFile)) {
            return $parentThemeFile;
        }
        
        $themeFilePath = $themeFile->getRelativePathname();
        if (preg_match('#^([a-zA-Z0-9]+)_([a-zA-Z0-9]+)/(.*)#', $themeFilePath, $match)) {
            $moduleName = $match[1] . '_' . $match[2];
            $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
            $parentThemeFile = $modulePath . '/view/frontend/' . $match[3];
            if (file_exists($parentThemeFile)) {
                return $parentThemeFile;
            }
            
            $parentThemeFile = $modulePath . '/view/base/' . $match[3];
            if (file_exists($parentThemeFile)) {
                return $parentThemeFile;
            }
        }
        
        $parentThemeFile = $this->viewFilesystem->getFilename($themeFile->getRelativePathname());
        if ($parentThemeFile !== false && file_exists($parentThemeFile)) {
            return $parentThemeFile;
        }
        
        // @todo: Support parent of parent of parent theme
        // @todo: Custom exception
        throw new Exception('Parent theme file not found');
    }
    
    /**
     * @param string $themePath
     * @return string
     */
    private function getParentThemeFromPath(string $themePath): string
    {
        $themeConfigFile = $themePath . '/theme.xml';
        $dom = new DOMDocument();
        $dom->load($themeConfigFile);
        $themeNode = $dom->getElementsByTagName('theme')->item(0);
        $themeParentNode = $themeNode->getElementsByTagName('parent')->item(0);
        return (string)$themeParentNode->nodeValue;
    }
}
