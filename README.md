# PurePerms

PurePerms is a maintained Axolotl-PM API 5 continuation that provides permissions and rank management for PocketMine-MP-compatible servers. This repository is maintained under [NhanAZ-Plugins](https://github.com/NhanAZ-Plugins) and keeps the original `PurePerms` identity, commands, permission nodes, providers, and data layout so existing installations can migrate without renaming their data.

## Compatibility

- Axolotl-PM API 5 (`api: [5.0.0]`), tested against the pinned 5.51.1 source in CI.
- PHP 8.4 for the development and build toolchain.
- JSON, SQLite3, and MySQL providers are retained.

Set `default-language: vie` in `config.yml` to use Vietnamese messages. The locale files live in `resources/lang/` and use these ISO 639-2 codes: `eng`, `vie`, `kor`, `rus`, `spa`, `ukr`, `zho`, `ind`, `tur`, `fra`, `por`, `deu`, `jpn`, and `ita`. English (`eng`) is the default.

The maintained baseline is `2.2.0`. GitHub Actions produces an auditable PHAR artifact for every push and pull request; releases and tags are created separately after runtime verification.

See [UPSTREAM.md](UPSTREAM.md) for provenance and the maintenance policy, and [CHANGELOG.md](CHANGELOG.md) for changes in this continuation.

# Commands

Command | Description | Permission
--- | --- | ---
`/addrank` | Adds a new rank | pperms.command.addrank
`/defrank <rank> [world]` | Allows you to set default rank. | pperms.command.defrank
`/plperms <plugin> [page]` | Lists permissions for a specific plugin. | pperms.command.plperms
`/fperms <plugin> [page]` | Legacy alias for listing plugin permissions. | pperms.command.fperms
`/listranks` | Shows a list of all ranks. | pperms.command.ranks
`/ppinfo` | Shows info about PurePerms. | pperms.command.ppinfo
`/rmrank <rank>` | Removes a rank from the rank list. | pperms.command.rmrank
`/setrank <player> <rank> [duration] [world]` | Sets a permanent or temporary rank. | pperms.command.setrank
`/unsetuperm <rank> <permission> [world]` | Removes a permission from the user. | pperms.command.unsetuperm
`/setuperm <player> <permission> [world]` | Adds a permission to the user. | pperms.command.setuperm
`/setgperm <group> <permission> [world]` | Adds a permission to a group. | pperms.command.setgperm
`/unsetgperm <group> <permission> [world]` | Removes a permission from a group. | pperms.command.unsetgperm
`/ppreload` | Reloads config, messages, provider-backed group data, and online player permissions. | pperms.command.ppreload

# Features

- Set up permissions for different rank!
- Multi-rank inheritance system to allow you to inherit rank permissions
- Supports JSON, SQLITe3 + MySQL providers
- Supports multi-world rank data when enabled in `resources/config.yml`.
- Supports temporary ranks with `1d2h3m`, `2h`, or `30m`; use `permanent` for a permanent rank.
- Reloads file-backed and MySQL-backed group data without restarting the server.
- Provides optional ScoreHud tags: `ppscore.rank`, `ppscore.prefix`, and `ppscore.suffix`.
- Provides simple and flexible PurePerms usage while retaining the legacy configuration format.

## Build locally

The repository uses [NhanAZ/DevTools](https://github.com/NhanAZ/DevTools) for reproducible PHAR builds and PHPStan Level Max analysis. The workflow is the canonical CI configuration. A local build from the workspace can be run with:

```powershell
php -d phar.readonly=0 .tooling/DevTools/bin/devtools.php build --project=. --out=build --overwrite --json
```

The command expects the DevTools checkout and an Axolotl-PM checkout used by the local PHPStan configuration. It does not create a GitHub release.
