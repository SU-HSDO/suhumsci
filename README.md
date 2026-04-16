# Stanford School of Humanities And Sciences HSDP

This codebase runs the Humanities and Sciences Drupal (or Digital) Platform a.k.a. HSDP.  This platform runs 130+ sites for departments, programs, centers, councils, initiatives, etc. The platform is hosted on Acquia.

## Overview

Note the following properties of this project:
* Stable branch: `develop`
* Release branch naming convention: `[VERSION]-release` e.g. `11.2.3-release`
* Four Kitchens secondary release branch naming convention: `fk-stnfd-sprint-[SPRINT_NUMBER]`
* Development branching convention: branch off the current release branch
* Local environment: DDEV, Lando or bare metal LAMP
* Local drush alias: @[SITE_ALIAS].local
* Local site URL: http://[SITE_ALIAS].suhumsci.loc

## BLT to SWS Drush Commands (SWSDC) migration
In April 2026 this repository moved from using BLT to SWS Drush Commands (SWSDC). If you are still using BLT and have not set up SWSDC, please read the [migration documentation](docs/BltToSWSDC.md).

## Documentation

- [Patching and patch management instructions](patches/README.md)
- [Codeception Tests](docs/Codeception.md)
- [Development Requirements](docs/DevelopmentRequirements.md)
- [Release and Code Deployment Process](docs/CodeDeploy.md)
- [Conding Standards](docs/CodingStandards.md)
- [Configuration Management](docs/Config.md)
- [Config & Content Update](docs/ConfigContentUpdate.md)
- [Launch Process](docs/Launch.md)
- [Provisioning a New Site](docs/NewSite.md)
- [Decommissioning and Deleting a Site](docs/DeleteSite.md)
- [Upgrading Drupal Core](docs/DrupalCoreUpgrades.md)

> **Note:** Not all documentation may be fully up to date. Maintaining and updating documentation is an ongoing and important process. Contributions and corrections are always welcome.

> **Note:** In addition to the documents listed here, there are various documentation and README files throughout the project (in subdirectories) that may be specific to certain features, modules, or workflows. Be sure to check those locations for more detailed or context-specific information.

## Development Requirements

See: [Development Requirements](docs/DevelopmentRequirements.md)

## Local setup and installation
You can either run the site on DDEV, Lando or bare metal.

#### Setup on DDEV or Lando
* [Follow the DDEV instructions](.ddev/DDEV-README.md).
* [Follow the Lando legacy instructions](lando/README.md).

#### Or setup on bare metal
1. Clone the repository and check out the develop branch.
1. Run `composer install`
1. Run `drush sws:multisite:settings`
1. Run `drush sws:keys`
1. **local.drush.yml**
  - Create or modify the `drush/local.drush.yml`
  - Add your local database settings and Acquia API key details. If used previously, your Acquia Key should be able to be found with `cat ~/.acquia/cloud_api.conf`. Otherwise login to Acquia to create a new one.
  ```
  command:
  sws:
    options:
      db-port: '3306'
      db-host: localhost
      db-user: admin
      db-pass: admin
      db-name: suhumsci
      app-key: <acquia-key>
      app-secret: <acquia-secret>
  ```
1. If you would like a clean installation run `drush sws:multisite:install`. Optionally, you can add the option `--site=[sitename]` if you wish to install to one of the multisites.
1. A full sync from a site should be accomplished with `drush drupal:sync --site=[sitename]`


## Builds

CSS assets are built using the Grunt task runner, but are run using npm scripts as shortcuts.

- `npm run theme-build` - Compile Sass for production for all themes based on `humsci_basic`.
- `npm run theme-watch` - Compile a CSS build and watch for changes in the existing `.scss` files in all themes based on `humsci_basic`.
- `npm run theme-visreg` - Run Percy VRT on `hs_colorful` and `hs_traditional` sites (see `docroot/themes/humsci/humsci_basic/README.md` for details.)

The build process will automatically choose whether to run in/out of ddev/lando (if installed).  To override this behavior use one of the following commands:

- `export HSDP_COMPILE_ENVIRONMENT=ddev`
- `export HSDP_COMPILE_ENVIRONMENT=lando`
- `export HSDP_COMPILE_ENVIRONMENT=baremetal`

## Testing

### Codeception
Acceptance testing and user testing id done use a testing framework [Codeception](https://codeception.com/). There is
very good documentation on codeception testing steps and how that is structured.

#### Codeception on bare metal
- `drush sws:codeception` will run all acceptance tests.
- `drush sws:codeception --group=[group-name]` will run tests that are annotated with the specified group. This is the most
  effective method to run a single test.
- [List of current tests](/docs/Codeception.md)

### SASS
- `npm test` - Run tests for all Sass in the project (including humsci_basic).


## Architecture Decision Records (ADRs)

Architecture Decision Records (ADRs) are used to document important architectural decisions made in this project. The ADR process and format are explained in [0000-record-architecture-decisions.md](docs/architecture/decisions/0000-record-architecture-decisions.md). All new significant architectural decisions should be documented as a new ADR in the `docs/architecture/decisions/` directory.

## Resources

* [GitHub](https://github.com/SU-HSDO/suhumsci)
* [Acquia Cloud subscription](https://cloud.acquia.com/a/applications/60ee2ebb-94f3-415d-a289-c23889ecec18)
