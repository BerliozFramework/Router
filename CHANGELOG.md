# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `RouterInterface::setServerParams()` supplies a non-serialized request context for URL generation; explicit `finalizePath()` parameters take precedence, and `$_SERVER` remains the fallback without a context
- `ForwardedPrefixResolver` centralizes prefix validation and proxy trust checks; `Router::getForwardedPrefixResolver()` exposes the resolver built from the concrete router's effective options
- `Router::finalizePath()` accepts an optional `?array $serverParams` argument (uses the current context, then `$_SERVER` when omitted), added to `RouterInterface`
- New `trustedProxies` router option: `X-Forwarded-Prefix` is now only honoured when `REMOTE_ADDR` is a trusted proxy (IP, CIDR or alias via `NetworkHelper::isTrustedProxy()`)

### Changed

- `Router::finalizePath()` normalizes leading/trailing slashes of the prefix and prefixes internal paths even when they start with the same segment as the proxy mount

### Security

- Ignore malformed forwarded prefixes, including header lists, traversal segments, delimiters, controls and ambiguous encoded separators or percent signs
- `X-Forwarded-Prefix` is ignored unless it comes from a configured trusted proxy, preventing a client from spoofing the prefix to poison generated URLs

## [3.2.1] - 2026-09-11

_No changes in this release._

## [3.2.0] - 2026-07-02

### Fixed

- Fix inline route attribute regex containing curly braces (quantifiers like `{2}` or `{1,3}`) is no longer truncated at the first closing brace

## [3.1.1] - 2026-06-10

_No changes in this release._

## [3.1.0] - 2026-05-18

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
