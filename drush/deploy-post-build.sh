#!/bin/bash

set -ev

# Cleanup directories and files and use a separate deploy .gitignore file to
# only commit and deploy the necessary files to Acquia.
mv drush/deploy.gitignore .gitignore
rm -rf .circleci .ddev .github .gitpod .tugboat blt lando patches tests

# Build frontend assets.
composer build-theme
