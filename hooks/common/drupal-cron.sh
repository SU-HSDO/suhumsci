#!/bin/bash
#
#

set -ev

target_env="$1"

# Prep for BLT commands.
repo_root="/var/www/html/humscigryphon.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt drupal:cron &>> /var/log/sites/humscigryphon.$target_env/logs/web-42199/drush-cron.log

set +v
