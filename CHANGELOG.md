# Change log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.0.0 rc1] - 2021-05-20
### Changed
  * [#38](https://github.com/drupal-composer/info-rewrite/pull/38): Composer 2 compatibility.
### Fixed
  * [#37](https://github.com/drupal-composer/info-rewrite/pull/37): Coding styles.
  * [#40](https://github.com/drupal-composer/info-rewrite/pull/40): Updating dev dependencies and fixing tests.

## [1.0.0] - 2021-05-20
### Fixed
  * [#13](https://github.com/drupal-composer/info-rewrite/pull/13): Fix tests.
  * [#15](https://github.com/drupal-composer/info-rewrite/pull/15): End info files with EOL character.
  * [#16](https://github.com/drupal-composer/info-rewrite/pull/16): Set core and package info.
  * [#19](https://github.com/drupal-composer/info-rewrite/pull/19): Don't run rollback on missing info files.
  * [#22](https://github.com/drupal-composer/info-rewrite/pull/22): Add core key only if missing core_version_requirement

### Added
  * [#17](https://github.com/drupal-composer/info-rewrite/pull/17): Additional package types.

### Changed
  * [#24](https://github.com/drupal-composer/info-rewrite/pull/24): Adjusted "Information added by line" for clarity.

## [1.0.0 beta1] - 2018-03-01
### Fixed
  * [#7](https://github.com/drupal-composer/info-rewrite/pull/7): Fatal error when no `.info` files exist.
  * [#10](https://github.com/drupal-composer/info-rewrite/pull/10): Drupal core had 4-digit version string.

[Unreleased]: https://github.com/drupal-composer/info-rewrite/compare/2.0.0-rc1...HEAD
[2.0.0 rc1]: https://github.com/drupal-composer/info-rewrite/compare/1.0.0...2.0.0-rc1
[1.0.0]: https://github.com/drupal-composer/info-rewrite/compare/1.0.0-beta1...1.0.0
[1.0.0 beta1]: https://github.com/drupal-composer/info-rewrite/compare/1.0.0-alpha1...1.0.0-beta1
