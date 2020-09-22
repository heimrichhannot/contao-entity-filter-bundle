# Changelog
All notable changes to this project will be documented in this file.

## [1.7.0] - 2020-09-22
- added `ModifyEntityFilterQueryEvent`
- added minRowCount = 0 per default

## [1.6.3] - 2020-04-17
- added tl_class to default dca data
- fixed php_cs

## [1.6.2] - 2020-04-17
- fixed flexbox style error for contao 4.9

## [1.6.1] - 2020-04-08
- contains all changes from 1.6.0, due an commit error no changes were added in 1.6.0

## [1.6.0] - 2020-04-07
- added check if foreign field exist to EntityFilter
- fixed non-public service

## [1.5.0] - 2019-03-19

### Change
- version 2 of `heimrichhannot/contao-multi-column-editor-bundle` as dependency

## [1.4.0] - 2019-01-22

### Change
- replaced `heimrichhannot/contao-field_value_copier` with `heimrichhannot/contao-field-value-copier-bundle`

## [1.3.1] - 2018-11-19

### Fixed
- symfony 4.x compatibility

## [1.3.0] - 2018-10-24

### Fixed
- replaced `heimrichhannot/contao-multi_column_editor` with `heimrichhannot/contao-multi-column-editor-bundle`

## [1.2.0] - 2018-03-12

### Added
- `huh.entity_filter.backend.entity_filter` now provides `computeQueryBuilderCondition` to properly support doctrine querybuilder

## [1.1.0] - 2018-03-12

### Changed
- added dependency to contao-utils-bundle 2.0.0

## [1.0.2] - 2018-03-02

### Changed
- reference `$GLOBALS['TL_LANG']['MSC']['operators']` to $GLOBALS['TL_LANG']['MSC']['databaseOperators']

## [1.0.1] - 2018-02-27

### Changed
- use `"heimrichhannot/contao-utils-bundle": "^1.0"` tagged version

## [1.0.0] - 2018-01-26

### Fixed
- initial commit
