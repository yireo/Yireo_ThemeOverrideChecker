# Yireo ThemeOverrideChecker

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-theme-override-checker)
<!-- badges.specs.end -->


Magento module to find all files of a given Magento theme (for example `Magento/luma`), compare the found files with the parent theme (and/or modules) and return whether there are differences or not.

**Current status**: Beta, ready for trying out

## Installation
```bash
composer require yireo/magento2-theme-override-checker:@dev
bin/magento module:enable Yireo_ThemeOverrideChecker
```


## Usage
List all possible overrides, with a warning for unneeded overrides (with no changes) and a red warning when lines are different:
```bash
bin/magento yireo:theme-overrides:check Magento/luma

```

List all specific override details for a specific file:
```bash
bin/magento yireo:theme-overrides:show Magento/luma web/jquery.js
bin/magento yireo:theme-overrides:show Magento/blank web/css/source/_theme.less
```

List a diff between the current version and the parent theme version:
```bash
bin/magento yireo:theme-overrides:diff Magento/luma web/css/source/_theme.less
```

## FAQ
### How does this work?
AmpersandHQ developed an excellent tool [ampersand-magento2-upgrade-patch-helper
](https://github.com/AmpersandHQ/ampersand-magento2-upgrade-patch-helper) to check the status of a current project. Part of this is that it also scans for override files in the theme. However, it does not report what overriding files actually means. For instance, is overriding an XML layout file good or bad? How many lines of overriding a JavaScript file classifies as a bad practice? 

## Todo
- Support for multiple parent themes
- Make `contextLines` in DifferGenerator configurable
- Improve adviser
  - An XML layout file with dozens of line differences is ok
  - An XML override file is dangerous
  - An PHTML / JS / CSS file with dozens of line differences is dangerous 
  - Markdown and text files can be the same
  - SVG files can be totally different

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeOverrideChecker/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeOverrideChecker/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeOverrideChecker/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeOverrideChecker/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ThemeOverrideChecker/compile.yml?label=compile)
<!-- badges.test.end -->
