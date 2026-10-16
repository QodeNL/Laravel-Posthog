# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-10-16

### Removed

- Laravel 11 support. Laravel 11 no longer receives security fixes; the package now requires Laravel 12 or 13.

### Changed

- Upgraded `posthog/posthog-php` from `^4.13` to `^4.14`. Feature flag payloads now resolve during local
  evaluation, and local evaluation matches the flags service more closely.

### Added

- `Posthog::getFeatureFlagPayload()` returns the decoded payload of a flag. It reads from the same cached
  evaluation as `getFeatureFlag()`, so it costs no extra `/flags` request, and works with local evaluation.
- `POSTHOG_RELEASE_ID` (`posthog.release_id`) is sent as `$release_id` on every event, including
  `$feature_flag_called`. Unlike the SDK's own `POSTHOG_RELEASE_ID` support, this works with `config:cache`
  and under PHP-FPM without `clear_env = no`. An explicit `$release_id` in the event properties wins.
- Error tracking. `Posthog::captureException()` sends an exception with the current distinct ID and groups.
  Set `POSTHOG_ERROR_TRACKING_ENABLED=true` (`posthog.error_tracking.enabled`) to report every exception
  that reaches Laravel's exception handler. Exceptions Laravel doesn't report are skipped, and an exception is
  sent anonymously when the distinct ID can't be resolved.
- `POSTHOG_CAPTURE_IP` (`posthog.capture_ip`, default `false`) sends the visitor's IP as `$ip` on capture,
  identify and captured exceptions, so GeoIP resolves to the user instead of the server running the queue worker. The IP is taken from
  `request()->ip()` at dispatch time, or from `Posthog::resolveIpUsing()` for proxies such as Cloudflare.
  An explicit `$ip` in the event properties wins.

## [1.4.0] - 2026-08-06

### Changed

- Upgraded `posthog/posthog-php` from `^3.7` to `^4.13`. This raises the SDK's own PHP requirement to
  `>=8.2` and adds `symfony/clock`; neither affects this package's `^8.4` requirement. The public API
  of this package is unchanged.
- Feature flags are now resolved with `PostHog::evaluateFlags()` instead of the per-flag
  `PostHog::getFeatureFlag()`, which the SDK deprecated in 4.3.0. The evaluation snapshot is cached
  per distinct ID for the duration of the request, so checking N flags for one user costs a single
  `/flags` request instead of N. `Feature::for($user)->values([...])` through the Pennant driver
  benefits the most.

### Added

- `POSTHOG_SECRET_KEY` (`posthog.secret_key`) for a personal API key (`phx_...`) or project secret
  key (`phs_...`). This is required for local feature flag evaluation — `evaluate_locally` had no
  effect without it, since the SDK could not fetch flag definitions.
- Configurable SDK options: `posthog.client.timeout`, `max_queue_size`, `batch_size`,
  `flush_interval_seconds`, `compress_request`, `consumer`, plus
  `posthog.feature_flags.request_timeout_ms` and `request_max_retries`. Options left unset keep the
  SDK default.
- `posthog.options` for passing any other posthog-php option straight through to `PostHog::init()`.

## [1.1.0] - 2024-03-16

### Added

- Feature Flag support

### Changed

- New configurations added to `config/posthog.php` file

## [1.0.4] - 2024-03-16

### Added

- Laravel 11 support

### Changed

- Change to the `posthogEnabled` check, to check if the Posthog key is set


## [1.0.3] - 2023-04-22

### Fixes

- Add extra check to check if the parameter name is set. 

## [1.0.2] - 2023-03-31

### Changed

- Readme update.

## [1.0.1] - 2023-03-24

### Added

- Distinct ID user prefix 
- Alias method 

## [1.0.0] - 2023-03-24

Initial release