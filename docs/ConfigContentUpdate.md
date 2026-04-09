# Config & Content Update

2026-04-06: This documentation needs to be updated. It is not recommended to follow these instructions -- leaving them here for reference when updates do take place.
2025-09-26: This documentation should be reviewed and updated, and may be slightly outdated.

Periodically the H&S team wants to make several changes to the default installation configurations & content. This often includes changes to the views, entity displays, entity forms, permissions, and some field settings. It also includes default content changes.

## Capturing Updates
After H&S has notified that they are done making changes, they should produce a list of all the changes that were made.
1. Capture configuration changes from the sandbox site
   1. `drush cpull @hs_sandbox.stage @self:../config/default`
   2. Go through the configs and remove unwanted changes. The `cpull` will pull all configuration currently on the staging
   site, but several configs are specific to the staging site or acquia environments. Compare the changes and clean up
   anything that shouldn't be committed
2. Pull the site to the local to capture content changes.
   1. `drush drupal:sync --sites=hs_sandbox`
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
