#!/bin/sh

set -ev

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt artifact:ac-hooks:post-code-update $site $target_env $source_branch $deployed_tag $repo_url $repo_type --environment=$target_env -v --yes --no-interaction

set +v
