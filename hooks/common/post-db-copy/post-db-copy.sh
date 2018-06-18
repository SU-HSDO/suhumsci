#!/bin/sh

set -ev

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"


echo "site: $site"
echo "target_env: $target_env"
echo "source_branch: $source_branch"
echo "deployed_tag: $deployed_tag"
echo "repo_url: $repo_url"
echo "repo_type: $repo_type"

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

#blt artifact:ac-hooks:post-code-update $site $target_env $source_branch $deployed_tag $repo_url $repo_type --environment=$target_env -v --yes --no-interaction
blt artifact:update:drupal:all-sites

set +v
