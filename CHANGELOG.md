# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 25 November 2026
### Added
- Support for Symfony 7 and sebastian/diff 6 and 7
- Support for PHP 8.5

### Changed
- Dropped support for PHP 8.1, Magento framework 101/102 (EOL) and sebastian/diff 1/2 (which failed at runtime)

### Fixed
- Security: the file argument of the show and diff commands could read files outside the theme directory (like ../../app/etc/env.php), including via symlinks
- Security: file contents, paths and the theme argument could inject console formatter tags and terminal escape sequences into the output
- Theme files were always also looked up under lib/, and an unregistered module could resolve to an absolute /view/... path
- Unknown themes or unresolvable files ended in uncaught exceptions that dumped absolute paths; a short error is now shown (trace with -v)
- A missing or malformed theme.xml caused PHP warnings and a fatal error
- An invalid --extension option was used unvalidated in the file search
- Large HTML comments made stripping fail with a TypeError
- PHP 8.4/8.6 deprecations for implicitly nullable parameters and trim() without explicit characters

## [0.0.5] - 12 September 2024
### Fixed
- PHPStan fixes

## [0.0.4] - 8 May 2024
### Fixed
- Add a command option to filter on file extension #3 @akosglue

## [0.0.3] - 28 August 2023
### Fixed
- Remove old imports and unused `$finder`

## [0.0.2] - 19 August 2023
### Added
- Improved advices
- Add better styling
- Lowered requirements for `sebastian/diff` (Magento 2.3)

## [0.0.1] - 2 August 2023
### Added
- Initial release
