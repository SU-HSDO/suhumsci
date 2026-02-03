# Configuration Management

Our platform uses a combination of contributed and custom modules to manage configuration safely across 100+ sites, balancing global control with site-level flexibility.

## config_ignore

- Prevents import and export of configuration that should be editable on individual sites, such as blocks, displays, and site-specific settings (homepage, 404, analytics, permissions).
- If a config is ignored and needs to be changed across all sites, changes must be made directly on the site or via a database update hook.
- Exception rules allow us to selectively import/export configs even if a broad ignore pattern is present.
- During config import, `config_ignore` uses the ignore rules from the codebase (`config_ignore.settings.yml` in your config export), not the current active config on the site.

## config_split

- Manages environment-specific configuration and modules (dev, stage, prod, local, ci, etc.,).
- Splits can be patch-based or complete splits in 2.x and use the config transformation pipeline for safe, granular config management.
- Ensures modules like `acquia_connector`, `purge`, and `stage_file_proxy` are enabled/disabled per environment.

## config_readonly & hs_config_readonly

- `config_readonly` locks the majority of configuration editing via the UI, ideal for production.
- `hs_config_readonly` allows dynamic unlocking of configs that are ignored, so site admins can edit only what’s intended.

## hs_config_prefix

- Automatically prefixes new site-created config (fields, views, displays, etc.) with `custm_` to distinguish site-specific config from global product config.
- Product-level config uses the `hs_` prefix.

## Partial Config Imports & hs_config_partial

- We use partial config imports to preserve custom site configuration.
- Partial imports only create or update config, never delete, so customizations are safe.
- If config needs to be deleted across all sites, a database update hook is required.
- Previously, partial imports were run using the `--partial` flag with `drush config-import`. With config_split 2.x and config_ignore 3.x, the config transformation pipeline is used, and `--partial` does not respect these modules.
- The custom `hs_config_partial` module implements safe partial import behavior using the transformation pipeline. The `--partial` flag is now deprecated and destructive. Do not use it.

## Best Practices

- Always use standard config import/export commands unless you have a specific reason to bypass `config_ignore` or `config_split`.
- Never use the `--partial` flag on environments with upgraded config modules and `hs_config_partial` enabled.
- For site-specific config changes or deletions, use update hooks or direct site editing.
- For local development, `config_ignore` and `config_split` can be overridden in settings files to match the local environment.
- Running `config-import` twice is a recommended approach. Certain configuration can require a fully configuration import before it is respected, especially with `config_split`.
- Use a database update hook to install or uninstall modules and do not rely on the config-import of the `core.extension.yml` to handle these. 


### Exporting config_split changes

To safely export configuration changes for a config_split, follow these steps:

1. **Disable all splits (including local):**
	 - In `docroot/sites/settings/local.settings.php`, set:
		 ```php
		 $config['config_split.config_split.local']['status'] = FALSE;
		 ```
	 - Rebuild cache:
		 ```sh
		 drush cr
		 ```
	 - Import default configuration (run twice to ensure all changes are applied):
		 ```sh
		 drush ci -y && drush ci -y
		 ```
	 - This ensures you start from a clean default config state and avoid mixing settings from other splits.

2. **Enable the desired split (e.g., dev):**
	 - In `local.settings.php`, set:
		 ```php
		 $config['config_split.config_split.dev']['status'] = TRUE;
		 ```
	 - Rebuild cache:
		 ```sh
		 drush cr
		 ```
	 - Import configuration for the split (run twice):
		 ```sh
		 drush ci -y && drush ci -y
		 ```

3. **Make your changes:**
	 - Apply the desired configuration changes in the UI or via code.

4. **Export configuration:**
	 - Export config:
		 ```sh
		 drush config-export -y
		 ```
	 - Note: Split-specific configuration is not shown in the export output. Use `git status` to verify exported changes in split directories.

5. **Revert split overrides:**
	 - In `local.settings.php`, comment out or remove the split status overrides. This prevents accidental imports/exports using the wrong split in future work.

6. **Restore your environment:**
	 - Import configuration, install a fresh site, or pull down a fresh site as needed. Always ensure the correct splits are enabled before making further changes locally.

**Tips:**
- Always disable all splits and import default config before enabling a new split for export.
- Running config import twice helps ensure all config is fully applied, especially with config_split.
- Use `git status` to confirm exported changes, as split config may not appear in the standard export output.
