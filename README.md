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
1. Run `blt local:setup` and answer the questions to configure your database settings.
1. Run `blt humsci:keys` to obtain necessary encryption keys.
1. If you would like a clean installation run `drush si config_installer -y`.
1. A full sync from a site should be accomplished with `blt drupal:sync --sync-files --site=[sitename]`
1. If you plan to use drupal console, and `drupal` produces an error, try the steps found on [this comment](https://github.com/hechoendrupal/drupal-console/issues/3302#issuecomment-306590885)

## Other documentation
* [Change Log](docs/CHANGELOG.md)
* [Code Deployment Process](docs/CodeDeploy.md)
* [Configuration Management Information](docs/Config.md)
* [Launch Processes](docs/Launch.md)
* [SSL Certificate Information](docs/LetsEncrypt.md)
* [SAML Information](docs/SimpleSAML.md)
* [New Site](docs/NewSite.md)

## Resources

* JIRA - link me!
* GitHub - link me!
* Acquia Cloud subscription - link me!
* TravisCI - link me!

[![Maintainability](https://api.codeclimate.com/v1/badges/fa85d434c3928bbf8d80/maintainability)](https://codeclimate.com/github/SU-HSDO/suhumsci/maintainability)
[![CircleCI](https://circleci.com/gh/SU-HSDO/suhumsci/tree/develop.svg?style=svg)](https://circleci.com/gh/SU-HSDO/suhumsci/tree/develop)
