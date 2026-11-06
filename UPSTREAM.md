# Upstream and maintenance policy

## Provenance

- Original project: [poggit-orphanage/PurePerms](https://github.com/poggit-orphanage/PurePerms).
- Archived plugin page: [Poggit Archive PurePerms](https://poggit-archive.github.io/plugins/203-pureperms/).
- API 5 reference port: [DemonicDev/PurePerms-API5](https://github.com/DemonicDev/PurePerms-API5).
- Maintained repository: [AxolotlBucketLabs/PurePerms](https://github.com/AxolotlBucketLabs/PurePerms).

The API 5 fork is used as a reference for the minimum event and manifest changes. It is not copied blindly: this repository keeps the upstream plugin identity and data contract, then applies typed, reviewable fixes required by current Axolotl-PM and PHPStan Level Max.

## Compatibility policy

PurePerms targets Axolotl-PM API 5. The manifest intentionally remains `PurePerms` with the original namespace and permission nodes. API 4 support is out of scope for this maintained continuation.

Changes should preserve:

- `plugin.yml` name, main class, commands, permissions, and provider names;
- `resources/config.yml`, rank data, player data, and provider formats;
- public method names used by PureChat and other existing plugins.

When a PocketMine-MP or Axolotl-PM API change requires a compatibility fix, document it in the changelog and add a focused runtime or static-analysis check where practical.

## Build and release policy

CI uses a full commit pin for DevTools, Axolotl-PM, setup-php-action, and GitHub Actions. CI uploads a PHAR artifact but does not publish a release. A version tag is allowed only after API-floor/runtime checks and maintainer review.
