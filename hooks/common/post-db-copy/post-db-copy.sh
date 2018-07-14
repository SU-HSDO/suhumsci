#!/bin/sh

set -ev

# swshumsci
site="$1"
# dev, test, prod
target_env="$2"
# archaeology
database="$3"
# prod
from_env="$4"


echo "site: $site"
echo "target_env: $target_env"
echo "database: $database"
echo "from_env: $from_env"

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

#blt artifact:ac-hooks:post-code-update $site $target_env $source_branch $deployed_tag $repo_url $repo_type --environment=$target_env -v --yes --no-interaction
blt artifact:update:drupal --site=database --environment=$target_env

set +v
