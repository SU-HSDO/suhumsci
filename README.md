# Stanford School of Humanities And Sciences HSDP

This codebase runs the Humanities and Sciences Drupal (or Digital) Platform a.k.a. HSDP. This platform runs 130+ sites for departments, programs, centers, councils, initiatives, etc. The platform is hosted on Acquia.

## Overview

Note the following properties of this project:
* Default development branch: the current major version branch, e.g. `12.x`
* Production release branch: `main`
* Four Kitchens secondary release branch naming convention: `fk-stnfd-sprint-<SPRINT_NUMBER>`
* Development branching convention: branch off the current major development branch
* Local environment: DDEV, Lando or bare metal LAMP
* Local drush alias: `@<SITE_ALIAS>.local`
* Local site URL: `http://<SITE_ALIAS>.suhumsci.loc`

## BLT to SWS Drush Commands (SWSDC) Migration

In April 2026 this repository moved from using BLT to SWS Drush Commands (SWSDC). If you are still using BLT and have not set up SWSDC, please read the [migration documentation](docs/BltToSWSDC.md).

## Documentation

### Standards & Reference

- [Branching Strategy](docs/BranchingStrategy.md)
- [Coding Standards](docs/CodingStandards.md) (PHPStan, PHPCS, etc.)
- [Configuration Management](docs/Config.md)
- [Development Requirements](docs/DevelopmentRequirements.md)

### Process Guides

- [Release and Code Deployment](docs/CodeDeploy.md)
- [Site Launch](docs/Launch.md)
- [Provisioning a New Site](docs/NewSite.md)
- [Decommissioning and Deleting a Site](docs/DeleteSite.md)
- [Upgrading Drupal Core](docs/DrupalCoreUpgrades.md)
- [Patching](docs/Patching.md)
- [Codeception Tests](docs/Codeception.md)

### Migration

- [BLT to SWSDC Migration](docs/BltToSWSDC.md)

> **Note:** Additional README files exist throughout the project in subdirectories for component-specific documentation. If you notice documentation that is incorrect or outdated, open a pull request with corrections.

## Development Requirements

See [Development Requirements](docs/DevelopmentRequirements.md).

## Local Setup and Installation

You can run the site on DDEV, Lando, or bare metal.

### Setup on DDEV or Lando
* [Follow the DDEV instructions](.ddev/DDEV-README.md).
* [Follow the Lando legacy instructions](lando/README.md).

### Setup on Bare Metal

1. Clone the repository and check out the current major development branch, for example `12.x`.
1. Run `composer install`
1. Run `drush sws:multisite:settings`
1. Run `drush sws:keys`
1. Create or modify `drush/local.drush.yml` and add your local database settings and Acquia API key details. If used previously, your Acquia key can be found with `cat ~/.acquia/cloud_api.conf`. Otherwise log in to Acquia to create a new one.
   ```yaml
   command:
     sws:
       options:
         db-port: '3306'
         db-host: localhost
         db-user: <DB_USER>
         db-pass: <DB_PASS>
         db-name: <DB_NAME>
         app-key: <ACQUIA_KEY>
         app-secret: <ACQUIA_SECRET>
   ```
   > **Warning:** Never commit `drush/local.drush.yml` or any file containing credentials to the repository. Verify credential files are listed in `.gitignore` before committing.
1. For a clean installation run `drush sws:multisite:install`. Optionally add `--site=<SITENAME>` to install a specific multisite.
1. To sync from a live site: `drush drupal:sync --site=<SITENAME>`

## Builds

CSS assets are built using the Grunt task runner via npm scripts.

- `npm run theme-build` — Compile Sass for production for all themes based on `humsci_basic`.
- `npm run theme-watch` — Compile a CSS build and watch for changes in the existing `.scss` files in all themes based on `humsci_basic`.
- `npm run theme-visreg` — Run Percy VRT on `hs_colorful` and `hs_traditional` sites (see `docroot/themes/humsci/humsci_basic/README.md` for details.)

The build process automatically detects whether to run inside DDEV or Lando. To override:

- `export HSDP_COMPILE_ENVIRONMENT=ddev`
- `export HSDP_COMPILE_ENVIRONMENT=lando`
- `export HSDP_COMPILE_ENVIRONMENT=baremetal`

## Testing

### Codeception

Acceptance testing is done using the [Codeception](https://codeception.com/) framework.

- `drush sws:codeception` — Run all acceptance tests.
- `drush sws:codeception --group=<GROUP_NAME>` — Run tests annotated with the specified group. This is the most effective method to run a single test.
- [List of current tests](docs/Codeception.md)

### Sass

- `npm test` — Run tests for all Sass in the project (including humsci_basic).

## Architecture Decision Records (ADRs)

Architecture Decision Records (ADRs) are used to document important architectural decisions made in this project. The ADR process and format are explained in [0000-record-architecture-decisions.md](docs/architecture/decisions/0000-record-architecture-decisions.md). All significant architectural decisions should be documented as a new ADR in the `docs/architecture/decisions/` directory.

## Glossary

| Term | Definition |
|---|---|
| HSDP | Humanities and Sciences Drupal Platform — the name for this multi-site Drupal application |
| SWSDC | SWS Drush Commands — the custom Drush command library that replaced BLT. Commands are prefixed with `drush sws:` |
| `<major>.x` | The current major version development branch (e.g., `12.x`). This is the default branch and base for all feature work |
| `<major>.x-build` | The deployment artifact branch generated from `<major>.x` and deployed to staging |
| Staging | The pre-production Acquia environment. Called "staging" in documentation and "test" in Acquia infrastructure, ACLI commands, and drush aliases (e.g., `@<SITENAME>.test`) |
| Drush alias | Site identifier used in drush commands. Derived from the site URL: dashes become underscores (`_`), dots become double underscores (`__`). Example: `my-site.stanford.edu` → alias `my_site` |
| ADR | Architecture Decision Record — a short document capturing a significant architectural decision, its context, and its consequences. Stored in `docs/architecture/decisions/` |
| Artifact tag | A deployable release tag in the format `YYYY-MM-DD_VERSION` (e.g., `2026-06-03_12.1.1`) pushed to Acquia Cloud by release automation |

## Resources

* [GitHub](https://github.com/SU-HSDO/suhumsci)
* [Acquia Cloud subscription](https://cloud.acquia.com/a/applications/60ee2ebb-94f3-415d-a289-c23889ecec18)
