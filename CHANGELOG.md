# Changelog

All notable changes to `siberfx/laravel-gemini` are documented in this file.

## [2.0.0] - 2026-09-29

Maintained by [siberfx](https://github.com/siberfx). Requires PHP 8.4 or 8.5 and Laravel 12 or 13 (Laravel 11 support dropped).

### Breaking

- Supported platforms are now PHP 8.4 / 8.5 and Laravel 12 / 13 only (Laravel 11 dropped).
- `Gemini::setApiKey()` now returns the `Gemini` instance (chaining `->text()` etc. still works).
- Custom builders implement `capability(): Capability` instead of `getCapability(): string`; `generate()` is typed `BaseResponse`.
- `temperature` and `maxTokens` are only sent when set — the model defaults apply otherwise (previously `0.7` / `1024` were always sent).
- The stream callback is invoked once per returned part.
- Default `retry_policy.max_retries` lowered from `30` to `3`.
- `ProviderInterface` now includes the cached-content methods; `CacheBuilder` / `FileBuilder` depend on the interface.

### Added

- `GenerationMethod` and `FileType` enums.
- Response helpers: `get()`, `parts()`, `finishReason()`, `jsonSerialize()`; `TextResponse::decode()` and `functionCalls()`; `ImageResponse::mimeType()`; `nextPageToken()` / `cachedContents()` on list responses.
- `long_running.poll_interval` / `long_running.timeout` config for `predictLongRunning` jobs.
- Test suite (Testbench + PHPUnit), Pint config and GitHub Actions CI.

### Fixed

- Responses were instantiated from the old `HosseinHezami\…` namespace, causing a fatal error.
- `cachedContent()` was never sent to the API.
- Streaming now requests `alt=sse`, so SSE lines are actually received.
- Typed exceptions (`AuthenticationException`, `RateLimitException`, …) are thrown with the API's error message instead of being swallowed by retries or re-wrapped as `ApiException`.
- Retries only happen on connection errors, `429` and `5xx`, honouring `Retry-After`.
- History no longer replaces attached files; function declarations use the `tools: [{functionDeclarations}]` shape; multi-speaker voices use the correct `voiceConfig` structure.
- Long-running video polling uses the correct path, has a timeout, reports operation errors and downloads the generated video.
- WAV headers are written little-endian.
- Per-request headers (e.g. upload headers) no longer leak into later requests.
- File uploads use the documented resumable upload protocol.
- Config keys aligned with the code (`caching.max_page_size`, `default_speech_config`).
- Removed the `post-autoload-dump` script that ran `artisan` inside the package.
