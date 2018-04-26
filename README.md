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
2. Run a `composer install --prefer-source` answer yes to any questions during this step.
3. Run `blt local:setup` and answer the questions to configure your database settings.
4. If you would like a clean installation run `drush si su_humsci_profile -y`.

## Resources

* JIRA - link me!
* GitHub - link me!
* Acquia Cloud subscription - link me!
* TravisCI - link me!
