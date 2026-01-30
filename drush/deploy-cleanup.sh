#!/bin/bash

set -ev

mv drush/deploy.gitignore .gitignore
rm -rf .circleci .ddev .github .gitpod .tugboat blt lando patches tests
