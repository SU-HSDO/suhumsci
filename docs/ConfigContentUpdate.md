# Config & Content Update

Periodically the H&S team wants to make several changes to the default installation configurations & content. This often
includes changes to the views, entity displays, entity forms, permissions, and some field settings. It also includes
default content changes.

## Preparation
H&S has been using the site [HS Sandbox](https://hs-sandbox-stage.stanford.edu) for this purpose. It is best to install
the site with a fresh installation and lock the staging environment to a tag to avoid any unwanted code deployments to
the staging environment.
1. `blt deploy --commit-msg="Defaults Update" --tag=DEFAULTS-[YYYY-MM-DD]`
2. Deploy that tag to the staging environment in the ACE dashboard
3. (recommended) Delete all files on the server for that site.
   1. `drush @default.stage ssh`
   2. `rm -rf /mnt/gfs/humscigryphon.test/sites/hs_sandbox/files/*`
4. Install the site clean `drush @hs_sandbox.stage si su_humsci_profile -y`
5. Notify H&S that the site is prepared and available for new defaults.

## Capturing Updates
After H&S has notified that they are done making changes, they should produce a list of all the changes that were made.
1. Capture configuration changes from the sandbox site
   1. `drush cpull @hs_sandbox.stage @self:../config/default`
   2. Go through the configs and remove unwanted changes. The `cpull` will pull all configuration currently on the staging
   site, but several configs are specific to the staging site or acquia environments. Compare the changes and clean up
   anything that shouldn't be committed
2. Pull the site to the local to capture content changes.
   1. `blt drupal:sync --sites=hs_sandbox`
   2. rename the default content directory: `cd docroot/profiles/humsci/su_humsci_profile/modules/humsci_default_content && mv content old_content`
   3. enable the default content module: `drush @hs_sandbox.local en humsci_default_content -y`
   4. restore the content directory: `mv old_content content`
   5. export default content: `drush @hs_sandbox.local dcem humsci_default_content`
      1. if any errors occur, work through those
   6. export new default content
      1. If a new node/user/shortcut/media are added, export those to the respective directories
         1. `drush @hs_sandbox.local dce [entity_type] [entity_id]`
         2. Or find out the uuid of the entities, add them to the `humsci_default_content.info.yml`, clear caches, and run `drush @hs_sandbox.local dcem humsci_default_content`
   7. Shortcuts needs some special attention. They only currently work as json files, not yml.
      1. Add all current changes to git: `git add -A`
      2. At the root directory: `composer require drupal/default_content:^2.0`
      3. `drush @hs_sandbox.local cr`
      4. `drush @hs_sandbox.local dcem humsci_default_content`
      5. add the shortcut and reset all other directories. `git add *shortcut/*; git clean -d -f; git checkout composer.*`
