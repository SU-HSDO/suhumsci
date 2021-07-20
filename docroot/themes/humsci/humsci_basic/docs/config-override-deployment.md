# Deploying new components without impacting legacy and existing site configuration(s)

Stanford University Humanities & Sciences supports the creation of multiple websites. Sparkbox is assisting with the implementation of new themes to support updates to their client's sites. The themes that currently exist can be categorized as legacy and current.

## Legacy site themes:
* SU_HUMSCI_THEME (base theme)
  * Archaeology
  * France-Stanford
  * Mathematics
  * Stanford HumSci SubTheme

## Current site themes:
* Humsci Basic (base theme)
  * Humsci Colorful
  * Humsci Traditional
  * Humsci Airy (future)

## Post Update functions
Post update functions allow us to override Humsci Basic theme settings. New components / paragraph type configurations and exisiting component configurations are available to all sites (legacy & current). When configurations are added or updated, we may not want those changes on all sites.

Post update functions allow us to target and prevent new configurations from being available for use on legacy sites. We need to prevent new features from breaking the functionality and presentation of legacy sites.

- Steps for adding a new function
- Reasons or description for each option to the functions

Link: https://github.com/SU-HSDO/suhumsci/blob/develop/docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.post_update.php

Provide example. Step through how it works.