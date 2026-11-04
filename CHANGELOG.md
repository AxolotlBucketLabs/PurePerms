# Changelog

## 2.1.0 - maintained API 5 baseline

- Continued the original PurePerms plugin under NhanAZ-Plugins.
- Targeted Axolotl-PM API 5 while preserving the `PurePerms` identity and data providers.
- Added typed command, provider, rank, player-data, event, and MySQL paths for PHPStan Level Max.
- Added Vietnamese message resources selectable with `default-language: vie` while keeping English as the default.
- Replaced the deprecated PHP 8.4 `mysqli::ping()` health check with a lightweight `SELECT 1` query.
- Added Russian messages, `/setgperm`, `/unsetgperm`, `/fperms`, and `/ppreload`.
- Added validated temporary-rank durations and provider-level reload support.
- Moved locale files to `resources/lang/` and added all 14 requested ISO 639-2 locales.
- Added reproducible DevTools PHAR builds and CI static analysis.
- No automatic GitHub release or tag is created by CI.
