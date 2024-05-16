# Code Deployment

This document gives a general overview of the code deployment workflow. Github Actions workflows automate the code deployments to Acquia.

**SWS Developers**: Please refer to internal Confluence documentation for more detailed and technical step-by-step instructions for production deployments.

## Release Branch
A release branch is named using the format `VERSION-release`. All code changes in a release branch must go through a PR. To merge a PR into a release branch it must:
- Pass all required github actions checks (Primarily CodeCeption and PHPUnit tests)
- Receive an approved PR review

When code is merged into a release branch, the branch runs through tests again, creates a new code artifact and pushes it to Acquia. The code artifact for the release branch is active on the staging environment. This way the most up to date release branch is always available on the staging environment for review.

## Production Deployment Process
Once a release branch is ready to be released and deployed to production it is merged into the `develop` branch. When a release branch is merged into the `develop branch`, it triggers github actions.

The github actions:
- Create a code artifact for deployment and pushes it up to Acquia
- Create a new tag and release on github based on the SEMVER patch/minor/major PR label
- Create a new release branch for the next release and open a PR

Once the code artifact is pushed to Acquia, it can be deployed to production.
