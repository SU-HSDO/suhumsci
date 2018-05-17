#!/bin/bash

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

drush --root=/var/www/html/[site].[target_env]/docroot @sites updatedb --yes
drush --root=/var/www/html/[site].[target_env]/docroot @sites cr
