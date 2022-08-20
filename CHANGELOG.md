# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- renamed the project to Croft
- renamed source to src
- moved a bunch of architectural code into lib\a6a

### Removed
- a bunch of dev dependencies
- composer.lock

### Fixed
- run-tests using an sh environment, syntax requires bash

### Changed
- Fiery-slash PHPCS ruleset (more strict commenting requirements)

## [0.1.1] 2018-06-19
### Changed
- Fixed bad path "/../web/docs" -> "./public/docs" in phpdox configuration

## [0.1.0] 2018-06-07
### Added
- This CHANGELOG to track what's happening here.
- An altogether too brief README.md
- Licensed (MIT) this work, see LICENSE.md for details
- Added CONTRIBUTING.md, for clarity
- Initialized the project with composer
- Setup a branch-alias
- phpqa configuration
- phpcs standard (fiery slash)
- phpmd configuration (aww snap)
- behat configuration
- phpdox configuration
- phpspec configuration
- test bootstrapping code (file-system, pathing constants)
- test runner, run-tests
- local behat.custom.yml support
- detailed phpcs tweak for tab indenting
- behat context for public/web stuffs
- 404 feature
- homepage feature
