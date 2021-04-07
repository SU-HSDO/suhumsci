#!/bin/bash
#
#

set -ev

target_env="$1"

# Prep for BLT commands.
repo_root="/var/www/html/swshumsci.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt drupal:cron &>> /var/log/sites/swshumsci.$target_env/logs/srv-7503/drush-cron.log

set +v
