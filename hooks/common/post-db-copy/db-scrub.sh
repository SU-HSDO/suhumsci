#!/bin/sh
#
# db-copy Cloud hook: db-scrub
#
# Scrub important information from a Drupal database.
#
# Usage: db-scrub.sh site target-env db-name source-env

set -ev

site="$1"
target_env="$2"
db_name="$3"
source_env="$4"

# Prep for commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

# Run cache rebuild before sanitization, otherwise errors may occur due to
# code changes between environments and the entire post-db-copy hook will cease
# to run.
drush cr --uri=$db_name
drush sql-sanitize --ignored-roles=decoupled_site_users --uri=$db_name

set +v
