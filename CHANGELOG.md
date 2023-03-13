# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/orisai/data-sources/compare/1.0.0...HEAD)

### Changed

- Composer
	- allows nette/utils ^4.0.0

## [1.0.0](https://github.com/orisai/data-sources/releases/tag/1.0.0) - 2023-01-22

### Added

- `DataSource` interface
	- `DefaultDataSource`
- `FormatEncoder` interface
	- `JsonFormatEncoder`
	- `NeonFormatEncoder`
	- `YamlFormatEncoder`
- `EncodingFailure` exception
- `NotSupportedType` exception
