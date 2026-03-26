# Versioning Policy — B2BRouter PHP SDK

This document defines how the SDK version relates to B2BRouter API versions, and the rules for bumping major, minor, and patch versions.

## Two independent version systems

The B2BRouter ecosystem has two version numbers that move independently:

| Version | Format | Example | Who controls it |
|---------|--------|---------|-----------------|
| **SDK version** | SemVer (`major.minor.patch`) | `1.1.0` | This repository |
| **API version** | Date-based (`YYYY-MM-DD`) | `2026-03-02` | B2BRouter platform |

The SDK version reflects changes to the PHP library — its classes, methods, signatures, and behavior. The API version reflects changes to the B2BRouter REST API — its endpoints, request/response shapes, and business rules.

A user can upgrade the SDK without changing which API version they target, and vice versa.

## When to bump each version level

### Patch (`1.0.x`)

Bug fixes and internal changes that don't affect the public interface:

- Fix a bug in request serialization
- Update documentation
- Internal refactoring with no behavior change

### Minor (`1.x.0`)

New functionality that is backwards-compatible, **including new API version pins**:

- Change the default API version (e.g., `2025-10-13` → `2026-03-02`)
- Add new service classes or methods
- Add new configuration options

A minor release may change the default API version. Users who need the previous API version can pin it explicitly:

```php
$client = new B2BRouterClient('your-api-key', [
    'api_version' => '2025-10-13',  // Pin to previous API version
]);
```

### Major (`x.0.0`)

Changes that break the SDK's PHP interface:

- Remove or rename a public class, method, or constant
- Change a method signature in a non-backwards-compatible way
- Remove support for a PHP version
- Change default behavior that cannot be overridden by configuration

**Changing the default API version pin is not a major change**, because users can always override it.

## Rationale

B2BRouter releases 3–4 API versions per year. If each API version required a new SDK major version, the SDK would reach v5.0.0 within a year. This creates two problems:

1. **It overstates upgrade risk.** A major version signals "your PHP code will break." But a new API version pin doesn't break PHP code — it changes which API behavior the server returns by default, and it can be overridden.

2. **It confuses users.** Rapidly incrementing major versions suggests instability, when in practice the SDK interface is stable.

## New API endpoints and SDK compatibility

When the SDK adds support for a new B2BRouter API endpoint (e.g., a new service class), the service is included in the SDK as a **minor release**. The SDK does not validate whether your configured API version supports the endpoint — it sends the request and lets the B2BRouter server handle compatibility.

This means:

- If you upgrade the SDK and it includes a new service, but you pin an older API version, calling that service may return a server-side error (e.g., 404) if the endpoint doesn't exist in that API version.
- The SDK will never prevent you from making the call. The server is the source of truth.
- The CHANGELOG and service docblocks should note which API version introduced the endpoint, so users know what's required.

## API version migration guidance

When a minor release changes the default API version, the CHANGELOG should document:

1. The previous and new default API version
2. What changed in the B2BRouter API between those versions (removed fields, renamed parameters, new behavior)
3. How to pin the previous version if the user isn't ready to migrate
4. Migration examples for affected API changes (before/after code snippets)

This ensures users can upgrade the SDK safely and migrate to the new API version at their own pace. 
