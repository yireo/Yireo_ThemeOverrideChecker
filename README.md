# Yireo ThemeOverrideChecker

Magento module to find all files of a given Magento theme (for example `Magento/luma`), compare the found files with the parent theme (and/or modules) and return whether there are differences or not.

**Current status**: In development

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

## Todo
- Support for multiple parent themes
