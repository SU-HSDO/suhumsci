# Configuration Management

For this product, we want to keep track of several configurations, but we want to allow the addition of new 
configurations on a per site basis. We also don't want to restrict our users into some customizations. To do this we 
have implemented some customized workflows and configuration management techniques. 

## Config Ignore
For our tracked content types, we want to allow for customization of form displays and view displays. To do this we have 
configured the config_ignore to ignore any configs for our content types that are prefixed with `hs_`. We also need to
ignore specific settings which change for every site. Such as the home page, 404 page, google analytics and permissions.
All of these have been added to be ignored by configuration management.

Also with the addition of [this patch](https://www.drupal.org/project/config_ignore/issues/2857247) we have enabled the
ability to ignore particular config changes if the change is being ignored. For example, we are ignoring 
`system.theme:default` key. This allows for a site to change which theme it has set as default, giving us the ability to
create subthemes for site specific templates/css. If the site has a different value in the `system.theme:default` value
then config_ignore will ignore that value, and only export the `system.theme` file _if_ there are other changes to the
configuration file.

### Local Ignore
For local development, we don't really need or want to keep all the custom configurations form the site which we are 
currently working on. Although there are a couple of configurations that we want to ignore like the site settings, and 
which theme is being used.

The issue with changing the config ignore is that during a configuration import, the config ignore settings are still 
set to the original value such as production config ignore settings. So to get the correct config ignore settings on
local, we have set overrides in the `docroot/sites/settings/common.settings.php` file. The override simply reads the
config file and sets the override values. This allows for the config ignore to be set before the config import attempts
to make any changes. Doing so ensures we get accurate import and syncing to code base. Any changes to the config file in
`config/envs/local/config_ignore.settings.yml` will be instantly applied to the local environment without any need to
do a config import. But a config import should still be executed if any changes are applied or the change should be 
completed in the UI as well, so that on the next config export the file will still contain the changes.

### Blocks
With custom themes available to the user, custom blocks on those themes becomes and issue on local environments. When
a user enables a new subtheme, the blocks are copied and applied to the new theme as new config files. Syncing to that
site we want to retain those blocks so we have ignored them from the config management. But then on export, the block
config files are then created. So to prevent them from being committed, we've ignored them from git as well. That allows
us to make config changes on local environments quickly and safely without adding unwanted configs. We can still add
new block configs, but we have to deliberately tell git to add that particular file.

## Config Read Only
On the production environment we have implemented [config_readonly](https://www.drupal.org/project/config_readonly) module
but we have done some customizations on the way that is locks the configurations. Originally the module locks every 
configuration from being created or edited. This doesn't work in our project because we want the users to be able to
create custom views, fields, content types, etc. So we have build the hs_config_readonly module. This module changes
the service that config_readonly provides. It will lock any config form _if_ the config lives in the repository _and_
the config is not being ignored by config_ignore. This allows the user to clone a view, but not make changes to a view
if it lives in our product.

## Site specific config
We have a custom module [HS Config Prefix](../docroot/modules/humsci/hs_config_prefix) which will prefix all config
entities created through the UI. When developing new content types, or various entities for the product, we want the
prefix to be `hs_`. This will namespace our entities for global use. For site building on the production environment,
we have a config split which sets the prefix to be `custm_`. This will allow us to differentiate which entities are in
the product and which ones are on a particular site.
