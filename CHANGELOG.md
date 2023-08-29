# Change Log

All notable changes to this project will be documented in this file. This project adheres
to [Semantic Versioning] (http://semver.org/). For change log format,
use [Keep a Changelog] (http://keepachangelog.com/).

## [2.1.0] - 2023-08-29

### Added

- New type of attribute: "slug"
- New type of attribute: "uuid4"

### Deprecated

- Attribute type: "uuid" ; use "uuid4" instead

## [2.0.2] - 2022-12-01

### Fixed

- Optional attributes are empty value instead of not returned

## [2.0.1] - 2021-12-15

### Fixed

- `NULL` parameters aren't excluded from generation of route

## [2.0.0] - 2021-09-08

No changes were introduced since the previous beta 3 release.

## [2.0.0-beta3] - 2021-07-07

### Fixed

- `RouteAttributes` and multi-dimensional parameters doesn't work

## [2.0.0-beta2] - 2021-06-07

### Changed

- `Router::isValid()` accepts a string value
- Add `RouteAttributes` interface in signature of method `RouterInterface::generate()`

## [2.0.0-beta1] - 2021-04-29

### Changed

- `RoutingException` details all missing attributes to generate root

### Removed

- Remove multiple routes with same name

### Fixed

- Fixed `Route` with empty path
- Fixed serialization of sub routes

## [2.0.0-alpha1] - 2021-02-14

### Added

- Allow routes with same name
- Allow declaration of attribute type in path
- Allow optional part in path
- Route can be a group of routes with inheritance of attributes
- Used of \Generator class
- New Attribute object to manage attributes in routes
- Dependency with `psr/http-message` library
- Compilation concept to generate regex when necessary or before serialization of Route object

### Changed

- Refactoring
- Bump compatibility to PHP 8 minimum
- Route object can be a group of routes

### Removed

- Remove RouteGenerator class
- Remove dependency with `berlioz/php-doc` library
- Remove dependency with `berlioz/http-message` library
- Remove dependency with `mbstring` extension
- Remove RouteSet class

## [1.1.0] - 2020-11-05

### Added

- PHP 8 compatibility

## [1.0.1] - 2020-07-30

### Changed

- Method Route::filterParameters() with multidimensional parameters array fixed

## [1.0.0] - 2020-05-29

First version
