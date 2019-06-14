# Code Deployment

CircleCI handles branch and tag deployments. Communicate with others involved before 
changing code on any Acquia environment.

## Branches
Every branch with a pull request in github will be tested by behat and PHPUnit. 
If all those tests pass, a build will be deployed to Acquia. A branch named 
`feature-branch` will be deployed to Acquia as `feature-branch-build`. These 
branches are never to be used on the production environment. 

## Tags
When a release is ready for production, create the release in GitHub. A suggested
tool is [release-it](https://github.com/release-it/release-it). This tool takes the
commit log and produces a release in GitHub with the log and links to the commits. 
If using this tool, simply use the command `release-it patch` or `release-it minor`.
Use [semver](https://semver.org/) as the release tag. Once a release is created in 
GitHub, CircleCI will automatically build and deploy that tag to Acquia. A tag named
`8.1.0` will be deployed with the current date prepended, ie `2019-06-01_8.1.0`.

## Production Release
1. Create a release tag as instructed above.
1. Deploy the tag to the staging environment.
1. Copy files & databases from production environment to the staging environment for QA
   * Only copy up to 4 databases at a time. Do not attempt to copy all at one time.
1. Back up all databases on production environment
1. Choose the new tag for the production environment
1. Review production URLs.
