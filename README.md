# Stanford Humanities And Sciences

This is an Acquia BLT tool to assist in deploying code for Humanities and Sciences installation profile.

## Getting Started

This project is based on BLT, an open-source project template and tool that enables building, testing, and deploying Drupal installations following Acquia Professional Services best practices.

To set up your local environment and begin developing for this project, refer to the [BLT onboarding documentation](http://blt.readthedocs.io/en/latest/readme/onboarding/). Note the following properties of this project:
* Primary development branch: develop
* Local environment: DrupalVM
* Local drush alias: @my-project.local
* Local site URL: http://local.my-project.com

1. Clone the repository and check out the develop branch.
1. Run a `composer install --prefer-source` answer yes to any questions during this step.
1. Run `blt humsci:local:setup` and answer the questions to configure your database settings.
1. If you would like a clean installation run `blt drupal:install`. Optionally, you can add the option `--site=[sitename]` if you wish to install to one of the multisites.
1. A full sync from a site should be accomplished with `blt drupal:sync --site=[sitename]`

## Builds

CSS assets are built using the Grunt task runner, but are run using npm scripts as shortcuts.

- `npm run build` - Compile Sass for production for all themes, **except Humsci_basic** which requires additional Grunt tasks to compile sub-theme specific CSS.
- `npm run watch` - Compile a CSS build and watch for changes in the existing `.scss` files (other than humsci_basic)

## Testing

- `npm test` - Run linting for all Sass in the project (including humsci_basic).

## Other documentation
* [Change Log](docs/CHANGELOG.md)
* [Code Deployment Process](docs/CodeDeploy.md)
* [Configuration Management Information](docs/Config.md)
* [Launch Processes](docs/Launch.md)
* [SSL Certificate Information](docs/LetsEncrypt.md)
* [SAML Information](docs/SimpleSAML.md)
* [New Site](docs/NewSite.md)

## Resources

* [GitHub](https://github.com/SU-HSDO/suhumsci)
* [Acquia Cloud subscription](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a)

[![Maintainability](https://api.codeclimate.com/v1/badges/fa85d434c3928bbf8d80/maintainability)](https://codeclimate.com/github/SU-HSDO/suhumsci/maintainability)
[![CircleCI](https://circleci.com/gh/SU-HSDO/suhumsci/tree/develop.svg?style=svg)](https://circleci.com/gh/SU-HSDO/suhumsci/tree/develop)
