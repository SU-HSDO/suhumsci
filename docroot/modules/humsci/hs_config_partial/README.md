# hs_config_partial

This Drupal module prevents configuration deletions during config import, effectively mimicking the "partial import" behavior found in earlier versions of Drush, but unsupported by `config_split` 2+ and `config_ignore` 3+ which now use the Config Transformation API.

## Features
- Blocks deletion of any configuration that exists in the active store but is missing from the import set during a config import, except for config explicitly allowed to be deleted.
- Ensures only configuration additions and updates are processed, never removals (unless allowed).
- Controlled by a feature flag (`enabled`) in the module's configuration. The module and "enabled" setting must both be enabled.
- Allows selective deletion of config by prefix, controlled via the `hs_config_partial_allow_delete` setting through `settings.php`.
- No configuration form: enable/disable protection and manage allow-list via configuration management, Drush, config splits, or settings.php overrides.

## Combining with config_split
The partial import also prevents any configuration that would be deleted by `config_split` when switching between different splits, including configuration attached to a module getting uninstalled. If a module is being uninstalled but associated configuration is blocked from deletion, the config import will fail. This means all module uninstalls need to take place before the config import step, **unless** the config is explicitly allowed to be deleted via the `hs_config_partial_allow_delete` setting. This is the reason why the `hs_config_partial_allow_delete` setting was introduced.

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
- To force enable or disable add to your settings.php:
  ```php
  $config['hs_config_partial.settings']['enabled'] = TRUE; // or FALSE
  ```
- This will override any value in the database or config files.

### Allowing Specific Config Deletions
To allow certain configuration to be deleted during a partial import (for example, when uninstalling modules or cleaning up legacy config), define the `hs_config_partial_allow_delete` setting in your `settings.php` file:

```php
$settings['hs_config_partial_allow_delete'] = [
  'acquia_connector.',
  'purge.',
  'purge_queuer_coretags.',
  'ultimate_cron.job.',
  // Add more prefixes as needed.
];
```

Any configuration name that starts with one of these prefixes will be allowed to be deleted during config import. All other config will be protected from deletion.

**Why?**
- This approach gives you fine-grained control over which config can be deleted, reducing the risk of accidental data/config loss.
- It is especially useful for allowing safe removal of config for modules that are being uninstalled, or for cleaning up known legacy config, while still protecting custom or critical configuration.

**Note:** This setting is only available via `settings.php` and is not exposed as a Drupal configuration form or managed config. This ensures environment-specific and deployment-specific control, and prevents accidental changes via the UI or config management.
