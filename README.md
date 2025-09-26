# Stanford School of Humanities And Sciences HSDP

This codebase runs the Humanities and Sciences Drupal (or Digital) Platform a.k.a. HSDP.  This platform runs 130+ sites for departments, programs, centers, councils, initiatives, etc. The platform is hosted on Acquia.

## Overview

This project is based on [Acquia BLT](https://docs.acquia.com/acquia-cms/add-ons/blt) (Bacon Lettuce Tomato, or Build and Launch Tool), an open-source project template and tool that enables building, testing, and deploying Drupal installations following Acquia Professional Services best practices.

Note the following properties of this project:
* Stable branch: `develop`
* Release branch naming convention: `[VERSION]-release` e.g. `11.2.3-release`
* Four Kitchens secondary release branch naming convention: `fk-stnfd-sprint-[SPRINT_NUMBER]`
* Development branching convention: branch off the current release branch
* Local environment: DDEV, Lando or DrupalVM
* Local drush alias: @[SITE_ALIAS].local
* Local site URL: http://[SITE_ALIAS].suhumsci.loc


### Prerequisites

Make sure you have added your SSH key in [Acquia Cloud profile](https://accounts.acquia.com/account), and that it's saved in your `~/.ssh` folder.

### Local setup and installation
You can either run the site on DDEV, Lando or bare metal.

#### Setup on DDEV or Lando
* [Follow the DDEV instructions](.ddev/DDEV-README.md).
* [Follow the Lando legacy instructions](lando/README.md).

#### Or setup on bare metal
1. Clone the repository and check out the develop branch.
2. Run a `composer install --prefer-source` answer yes to any questions during this step.
3. Run `blt humsci:local:setup` and answer the questions to configure your database settings.
4. If you would like a clean installation run `blt drupal:install`. Optionally, you can add the option `--site=[sitename]` if you wish to install to one of the multisites.
5. A full sync from a site should be accomplished with `blt drupal:sync --site=[sitename]`


## Builds

CSS assets are built using the Grunt task runner, but are run using npm scripts as shortcuts.

- `npm run theme-build` - Compile Sass for production for all themes based on `humsci_basic`.
- `npm run theme-watch` - Compile a CSS build and watch for changes in the existing `.scss` files in all themes based on `humsci_basic`.
- `npm run theme-visreg` - Run Percy VRT on `hs_colorful` and `hs_traditional` sites (see `docroot/themes/humsci/humsci_basic/README.md` for details.)


## Testing

### Codeception
Acceptance testing and user testing id done use a testing framework [Codeception](https://codeception.com/). There is
very good documentation on codeception testing steps and how that is structured.

#### Codeception on bare metal
To run those tests locally, `blt` will
be the wrapper around the codeception commands.
- To run codeception first uninstall the SimpleSaml module `drush pmu simplesamlphp_auth -y`
- `blt codeception` will run all acceptance tests.
- `blt codeception --group=[group-name]` will run tests that are annotated with the specified group. This is the most
  effective method to run a single test.
- [List of current tests](/docs/Codeception.md)

### SASS
- `npm test` - Run tests for all Sass in the project (including humsci_basic).


## Architecture Decision Records (ADRs)

Architecture Decision Records (ADRs) are used to document important architectural decisions made in this project. The ADR process and format are explained in [0000-record-architecture-decisions.md](docs/architecture/decisions/0000-record-architecture-decisions.md). All new significant architectural decisions should be documented as a new ADR in the `docs/architecture/decisions/` directory.

## Documentation

- [Patching and patch management instructions](patches/README.md)
- [Codeception Tests](docs/Codeception.md)
- [Code Deployment Process](docs/CodeDeploy.md)
- [Conding Standards](docs/CodingStandards.md)
- [Configuration Management](docs/Config.md)
- [Config & Content Update](docs/ConfigContentUpdate.md)
- [Launch Process](docs/Launch.md)
- [Provisioning a New Site](docs/NewSite.md)

> **Note:** Not all documentation may be fully up to date. Maintaining and updating documentation is an ongoing and important process. Contributions and corrections are always welcome.

> **Note:** In addition to the documents listed here, there are various documentation and README files throughout the project (in subdirectories) that may be specific to certain features, modules, or workflows. Be sure to check those locations for more detailed or context-specific information.

## Resources

* [GitHub](https://github.com/SU-HSDO/suhumsci)
* [Acquia Cloud subscription](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a)
