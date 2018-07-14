#!/bin/sh

set -ev

# swshumsci
org="$1"
# dev, test, prod
target_env="$2"
# archaeology
database="$3"
# prod
from_env="$4"


echo "org: $org"
echo "target_env: $target_env"
echo "database: $database"
echo "from_env: $from_env"

# Prep for BLT commands.
repo_root="/var/www/html/$org.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt artifact:update:drupal --site=$database --environment=$target_env

set +v
