# Deploying new components without impacting legacy and existing site configuration(s)

Stanford University Humanities & Sciences supports the creation of multiple websites. Sparkbox is assisting with the implementation of new themes to support updates to their client's sites.

## Current site themes

* Humsci Basic (base theme)
  * Humsci Colorful
  * Humsci Traditional
  * Humsci Airy (future)

## Post Update functions

Post update functions allow us to override Humsci Basic theme settings. New components / paragraph type configurations and exisiting component configurations are available to all sites. When configurations are added or updated, we may not want those changes on all sites.

When you would like to add a new Post update function you will need to to update the file below:
https://github.com/SU-HSDO/suhumsci/blob/develop/docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.post_update.php

* You'll want to add a new function with the prefix `su_humsci_profile_post_update` to the function with a number assigned to the new function, example `su_humsci_profile_post_update_9013`. We assign a number that would be the next logical order to what has been added ex: 9012, 9013, 9014..
* You will need to write the function to disable or enable the component depending on how which option you'd like to do.

### Example(s)

```php
_su_humsci_profile_disable_paragraph('paragraph', 'hs_row', 'field_hs_row_components', 'hs_timeline_item');
_su_humsci_profile_enable_paragraph('node', 'hs_basic_page', 'field_hs_page_components', 'hs_gradient_hero_slider');
```

* How you fill in the function: `('entity_type', 'entity_name', 'field_region_name', 'field_name')`.
