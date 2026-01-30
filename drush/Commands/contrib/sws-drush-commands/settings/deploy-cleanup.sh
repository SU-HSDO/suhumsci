#!/bin/bash

set -ev

mv drush/deploy.gitignore .gitignore
rm -rf .circleci .github .gitpod .tugboat blt lando patches tests
