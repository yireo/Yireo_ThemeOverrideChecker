# Yireo ThemeOverrideChecker

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
- Make `contextLines` in DifferFactory configurable
- Improve adviser
  - An XML layout file with dozens of line differences is ok
  - An XML override file is dangerous
  - An PHTML / JS / CSS file with dozens of line differences is dangerous 
  - Markdown and text files can be the same
  - SVG files can be totally different
