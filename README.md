# PurePerms

PurePerms provides rank, inheritance and permission management for PocketMine-MP and Axolotl-PM API 5 servers.

[![CI](https://github.com/AxolotlBucketLabs/PurePerms/actions/workflows/build.yml/badge.svg)](https://github.com/AxolotlBucketLabs/PurePerms/actions/workflows/build.yml) [![Latest release](https://img.shields.io/github/v/release/AxolotlBucketLabs/PurePerms)](https://github.com/AxolotlBucketLabs/PurePerms/releases/latest) [![License](https://img.shields.io/github/license/AxolotlBucketLabs/PurePerms)](LICENSE) [![Discord](https://img.shields.io/discord/1568295845374206052?label=Discord&logo=discord&logoColor=white&color=5865F2)](https://discord.gg/Pk4FrNvHnM)

## Compatibility

- The plugin targets API 5 and is tested against Axolotl-PM 5.51.1 in CI.
- PHP 8.4 is used by the development and build workflow.
- JSON, SQLite3 and MySQL providers are retained.
- PureChat can use PurePerms for group and rank information.

## Installation

1. Download the `PurePerms.phar` file from the [latest release](https://github.com/AxolotlBucketLabs/PurePerms/releases/latest).
2. Copy the PHAR to the server's `plugins/` directory.
3. Start the server and review `plugin_data/PurePerms/config.yml`.

PurePerms creates its configuration on the first start. Set `default-language: vie` to use the Vietnamese translation. The available locale files are in `resources/lang/`.

## Features

- Define ranks and inherit permissions from other ranks.
- Store rank data with the retained JSON, SQLite3 or MySQL providers.
- Enable multi-world rank data in `resources/config.yml`.
- Define temporary ranks with values such as `1d2h3m`, `2h` or `30m`. Use `permanent` for a permanent rank.
- Reload file-backed and MySQL-backed group data without restarting the server.
- Expose `ppscore.rank`, `ppscore.prefix` and `ppscore.suffix` tags when ScoreHud is installed.
- Preserve the legacy configuration format used by the original project.

## Commands

| Command | Description | Permission |
| --- | --- | --- |
| `/addrank` | Add a rank. | `pperms.command.addrank` |
| `/defrank <rank> [world]` | Set the default rank. | `pperms.command.defrank` |
| `/plperms <plugin> [page]` | List permissions for a plugin. | `pperms.command.plperms` |
| `/fperms <plugin> [page]` | List plugin permissions through the legacy alias. | `pperms.command.fperms` |
| `/listranks` | List all ranks. | `pperms.command.ranks` |
| `/ppinfo` | Show PurePerms information. | `pperms.command.ppinfo` |
| `/rmrank <rank>` | Remove a rank. | `pperms.command.rmrank` |
| `/setrank <player> <rank> [duration] [world]` | Set a permanent or temporary rank. | `pperms.command.setrank` |
| `/unsetuperm <rank> <permission> [world]` | Remove a permission from a user. | `pperms.command.unsetuperm` |
| `/setuperm <player> <permission> [world]` | Add a permission to a user. | `pperms.command.setuperm` |
| `/setgperm <group> <permission> [world]` | Add a permission to a group. | `pperms.command.setgperm` |
| `/unsetgperm <group> <permission> [world]` | Remove a permission from a group. | `pperms.command.unsetgperm` |
| `/ppreload` | Reload configuration, messages and provider-backed data. | `pperms.command.ppreload` |

## Build locally

The repository uses [AxolotlBucketLabs DevTools](https://github.com/AxolotlBucketLabs/DevTools) for PHAR packaging and PHPStan level max analysis. The local build requires an Axolotl-PM source tree, as shown in the workflow.

```powershell
php -d phar.readonly=0 .tooling/DevTools/bin/devtools.php build --project=. --out=build --overwrite --json
```

The workflow produces an auditable PHAR artifact for each push and pull request. Releases and tags are created separately after runtime verification.

## Provenance

See [UPSTREAM.md](UPSTREAM.md) for the original project, retained attribution and maintenance policy. See [CHANGELOG.md](CHANGELOG.md) for maintained changes.
