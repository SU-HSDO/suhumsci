#!/bin/bash
#
# db-copy Cloud Hook: post-db-copy
#
# The post-code-update hook runs in response to database syncs. When you
# push commits to a Git branch, the post-code-update hooks runs for
# each environment that is currently running that branch.
#
# The arguments for post-code-update are the same as for post-code-deploy,
# with the source-branch and deployed-tag arguments both set to the name of
# the environment receiving the new code.
#
# post-code-update only runs if your site is using a Git repository. It does
# not support SVN.
set -ev

../update-db.sh $1 $2 $3 $4 $5 $6

set +v
