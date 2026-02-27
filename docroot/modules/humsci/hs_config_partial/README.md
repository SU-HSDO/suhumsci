# hs_config_partial

This Drupal module prevents configuration deletions during config import, effectively mimicking the "partial import" behavior found in earlier versions of Drush, but unsupported by `config_split` 2+ and `config_ignore` 3+.

## Features
- Blocks deletion of any configuration that exists in the active store but is missing from the import set during a config import.
- Ensures only configuration additions and updates are processed, never removals.
- Controlled by a feature flag (`enabled`) in the module's configuration. The module and "enabled" setting must both be enabled.
- No configuration form: enable/disable protection via configuration management, Drush, config splits, or settings.php overrides.

## Combining with config_split
The partial import also prevents any configuration that would be deleted by `config_split` when switching between different splits, including configuration attached to a module getting uninstalled. This means all module uninstalls need to take place before the config import step.

## Configuration

### Enable/Disable Protection
- The module uses a boolean config value: `hs_config_partial.settings:enabled`.
- By default, this is set to `TRUE` on install.
- There is **no configuration form**. Use one of the following methods:

#### Drush
- Enable:  `drush cset hs_config_partial.settings enabled 1`
- Disable: `drush cset hs_config_partial.settings enabled 0`
- Check:   `drush cget hs_config_partial.settings enabled`
- **Note:** Use `1` (enabled) and `0` (disabled). Using `true`/`false` as strings will always set to `TRUE`.

#### settings.php override
- To force enable or disable in a specific environment, add to your settings.php:
  ```php
  $config['hs_config_partial.settings']['enabled'] = TRUE; // or FALSE
  ```
- This will override any value in the database or config files.
