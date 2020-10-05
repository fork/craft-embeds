# Embeds Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased

### Added
- ...

### Changed
- ...

### Fixed
- ...

## [1.1.3] - 2020-06-10
### Fixed
- Error during plugin install / Matrix now requires at least one blocktype / log errors during migration

## [1.1.2] - 2019-11-13
### Fixed
- Error during plugin install (permissions) / Use README instructions instead

## [1.1.1] - 2019-11-13
### Added
- Settings for fieldnames in JS / Init from backend

## [1.1.0] - 2019-08-15
### Fixed
- replace all calls to `$element[$field-handle]` with `$element->getFieldValue($field->handle)` to avoid handle-based
confusion

## [1.0.7] - 2019-08-14
### Added
- New Javascript functionality (Disable/Enable Embeds / Supertable support)

## [1.0.6] - 2019-05-10
### Added
- Settings for copytext and embed fieldnames
- Setting to optionally change the date format for date fields
- Variable to use `craft.embeds.getElementData()` in twig

### Removed
- Legacy image transformation code

## [1.0.5] - 2019-04-30
### Added
- Parameter for ignoring Fields in the `getElementData()` function

## [1.0.4.3] - 2019-04-29
### Changed
- Stop one nesting level earlier

## [1.0.4.2] - 2019-04-17
### Fixed
- A pretty stupid recursion error

## [1.0.4.1] - 2019-04-17
### Fixed
- Increased nesting level

## [1.0.4] - 2019-04-16
### Fixed
- Carry nesting level when recursing to avoid infinite nesting

## [1.0.3] - 2019-03-12
### Changed
- Matrix fields with `maxBlocks` = 1 will now return that single block instead of an array

## [1.0.2] - 2019-01-28
### Removed
- Settings, for now

## [1.0.1] - 2019-01-28
### Added
- Settings

## [1.0.0] - 2018-08-13
### Added
- Initial release
