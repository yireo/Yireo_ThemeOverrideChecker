# Yireo ThemeOverrideChecker

Magento module to find all files of a given Magento theme (for example `Magento/luma`), compare the found files with the parent theme (and/or modules) and return whether there are differences or not.

**Current status**: In development

## Usage
```bash
bin/magento yireo:check-theme-overrides Magento/luma
bin/magento yireo:check-theme-overrides Magento/luma --diff 1
```