# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Host restrictions are now inherited from parent route groups
- Duplicate attribute names in route path now throw `RoutingException`

### Fixed

- Fix named route groups can now be found by `getRoute()`
- Fix inline regex `0` in route attribute is no longer silently dropped
- Fix `finalizePath()` URI scheme check no longer triggered by user parameters
- Fix `count()` now recursively counts all leaf routes including those in groups
- Fix route regex delimiter no longer conflicts with user-supplied requirement patterns
- Fix add PATCH to the list of default accepted HTTP methods
- Fix `float` route type now accepts integer values

## [3.0.0] - 2026-02-19

The version 3.0.0 marks the transition of the Berlioz Framework to a **Monorepo** structure.

Why this move?

- **Consistency:** Ensures all Berlioz components are tested and released together, preventing version mismatches.
- **Maintenance:** Simplifies the management of issues and pull requests by centralizing them in one place.
- **Better CI/CD:** Global testing ensures that a change in one package never breaks another.
