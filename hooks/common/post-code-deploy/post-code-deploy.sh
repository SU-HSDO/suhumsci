#!/bin/bash
#
# Cloud Hook: post-code-deploy
#
# The post-code-deploy hook is run whenever you use the Workflow page to
# deploy new code to an environment, either via drag-drop or by selecting
# an existing branch or tag from the Code drop-down list. See
# ../README.md for details.
#
# Usage: post-code-deploy site target-env source-branch deployed-tag repo-url
#                         repo-type

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

echo "site: $site"
echo "target_env: $target_env"
echo "source_branch: $source_branch"
echo "deployed_tag: $deployed_tag"
echo "repo_url: $repo_url"
echo "repo_type: $repo_type"


drush updb -y;
drush cr;

if [[ $target_env = *"ode"* ]]; then
  drush pmu simplesamlphp_auth
fi

set +v
