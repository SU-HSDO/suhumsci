# Code Deployment

## Development
1. Checkout latest code in the `develop` branch
1. Execute `compser install --prefer-source` to build all of the needed dependencies.
1. Commit any changes to the composer files.
1. Execute the blt command `blt deploy`
1. In the Acquia UI choose "develop-branch" on the development environment code.

## Staging
1. Checkout latest code in the `develop` branch
1. Execute `compser install --prefer-source` to build all of the needed dependencies.
1. Commit any changes to the composer files.
1. Execute the blt command `blt deploy`
1. When asked to create a tag, enter `Y`
1. Enter a tag prefaced with the current date in the format `YYYY-MM-DD_[tagname]`
    * For example a release tag would be `2019-01-01_8.1.2`
1. In the Acquia UI choose the tag you created for the code on the staging environment
1. After deployment has finished, review task logs for anything unexpected or errors.

## Production
1. Follow all the steps to deploy code to the Staging environment
1. Optionally: Copy files & databases from production environment to the staging environment for QA
1. Back up all databases on production environment
1. Choose the new tag for the production environment
1. Review production URLs.
