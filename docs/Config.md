# Configuration Management

The platform uses a combination of contributed and custom modules to manage configuration safely across 100+ sites, balancing global control with site-level flexibility.

## config_ignore

- Prevents import and export of configuration that should be editable on individual sites, such as blocks, displays, and site-specific settings (homepage, 404, analytics, permissions).
- If a config is ignored and needs to be changed across all sites, changes must be made directly on the site or via a database update hook.
- Exception rules allow selective import/export of configs even if a broad ignore pattern is present.
- Rules can match whole configs (`block.block.*`) or a single property (`config.name:property`, such as `field.field.node.hs_basic_page.field_hs_page_components:settings.handler_settings`). Prefer a property-level rule when only one key should diverge per site.
- Rules are added by hand-editing `config/default/config_ignore.settings.yml`; the `_core.default_config_hash` value is not recomputed for hand edits.
- By default, `config_ignore` reads its rules from sync storage (`config/default/config_ignore.settings.yml`) for both imports and exports. On local environments with a `config_split` that patches `config_ignore` settings, this means the local split's overrides are applied during import but silently bypassed during export, which causes local-only ignore rules to have no effect on `drush config-export`.
- To correct this, local settings files (`local.settings.php` and `default.local.settings.php`) set `$settings['config_ignore_storage'] = 'active'`. This tells `config_ignore` to read its rules from active configuration, including any config_split patches currently applied, for both imports and exports. As a result, locally-ignored configuration (such as role permissions) is correctly excluded from exports, and the local split's intent is fully respected in both directions. This setting is only needed where the local config_split is active; CI and Tugboat do not enable the local split, so active and sync config_ignore are always identical there.

> **Note:** This setting also means that during a site sync to local, `config_ignore` uses the active configuration on the site at the time of the sync. On a fresh site sync, the local split is not yet imported, so the first import uses the production ignore rules from `config/default`. Once the local split is imported, all subsequent imports and exports use the local split's rules.

## config_split

- Manages environment-specific configuration and modules (dev, stage, prod, local, ci, etc.).
- Splits can be patch-based or complete splits in 2.x and use the config transformation pipeline for safe, granular config management.
- Ensures modules like `acquia_connector`, `purge`, and `stage_file_proxy` are enabled/disabled per environment.

## config_readonly & hs_config_readonly

- `config_readonly` locks the majority of configuration editing via the UI, ideal for production.
- `hs_config_readonly` allows dynamic unlocking of configs that are ignored, so site admins can edit only what's intended.

## hs_config_prefix

- Automatically prefixes new site-created config (fields, views, displays, etc.) with `custm_` to distinguish site-specific config from global product config.
- Product-level config uses the `hs_` prefix.

## Partial Config Imports & hs_config_partial

- Partial config imports are used to preserve custom site configuration.
- Partial imports only create or update config, never delete, so customizations are safe **unless** the config is explicitly allowed to be deleted (see below).
- If config needs to be deleted across all sites, a database update hook is required, or you may allow deletion by adding a prefix to the allow-list.
- Previously, partial imports were run using the `--partial` flag with `drush config-import`. With `config_split` 2.x and `config_ignore` 3.x, the config transformation pipeline is used, and `--partial` does not respect these modules.
- The custom `hs_config_partial` module implements partial import behavior using the transformation pipeline. The `--partial` flag is now deprecated and destructive. Do not use it.
- The `acquia.settings.php` enables the `hs_config_partial` enabled setting on all Acquia environments, in addition to `config_split` to ensure this stays on.
- The partial import also prevents any configuration that would be deleted by `config_split` when switching between different splits, including configuration attached to a module getting uninstalled. If a module is being uninstalled but associated configuration is blocked from deletion, the config import will fail. This means all module uninstalls need to take place before the config import step, **unless** the config is explicitly allowed to be deleted via the `hs_config_partial_allow_delete` setting.
- Deletion of specific configurations is allowed through the `hs_config_partial_allow_delete` setting in the `settings.php` file. Currently this is only used to allow deletion of configuration associated with modules being uninstalled when syncing from different environments.
- For more information see the [hs_config_partial module README](../docroot/modules/humsci/hs_config_partial/README.md).

## New Sites vs Existing Sites

New and existing sites receive configuration through different paths, which makes it possible to apply a change only to sites provisioned from that point forward.

- New sites are installed with `drush si su_humsci_profile` (see [NewSite.md](NewSite.md)). The core installer imports `config/default` directly, without the transformation pipeline, so `config_ignore` and `config_split` do not apply during installation. New sites receive every config in `config/default`, including configs that are ignored on later imports (blocks, roles, etc.).
- The install profile's `config/sync` directory is a symlink to `config/default`, so the platform configuration lives in one place. Editing `config/default` is the only change needed.
- To make a value apply only to newly provisioned sites, set it in `config/default` and add a `config_ignore` rule for it (property-level where possible) so deploys never overwrite it on existing sites. Existing sites keep their current value, still changeable per site or via a database update hook; sites installed after the change start with the new value. For example, to limit Spotlight slides only on new sites, set the cardinality in `config/default/field.storage.paragraph.field_hs_sptlght_sldes.yml` and add the rule `field.storage.paragraph.field_hs_sptlght_sldes:cardinality`.
- Because `config_ignore` reads its rules from sync storage during an import, a new rule and the config change it protects can ship together in a single `drush ci`.
- Site copies (see [CopySite.md](CopySite.md)) inherit the source site's active configuration, not the values in `config/default`, so a copied site does not pick up new-site values.


## Deploy Hooks and Post-Config-Import Operations

Some operations must run after config import has completed. For example, granting permissions tied to a new content type that is itself created by config import. `hook_update_N()` runs before config import, so those operations will fail or be rolled back if placed in an update hook.

Use `hook_deploy_NAME()` (in a `MODULE.deploy.php` file) for any operation that depends on configuration existing in active storage first. `drush deploy` and `drush drupal:sync` both run `deploy:hook` automatically after config import. When running updates manually, always include `drush deploy:hook` after `drush config:import`.

See [ADR 0004](architecture/decisions/0004-use-deploy-hooks-for-post-config-operations.md) for the full decision and naming conventions used in this project.

## Best Practices

- Always use standard config import/export commands unless you have a specific reason to bypass `config_ignore` or `config_split`.
- Never use the `--partial` flag on environments with upgraded config modules and `hs_config_partial` enabled.
- For site-specific config changes or deletions, use update hooks or direct site editing.
- For local development, `config_ignore` and `config_split` can be overridden in settings files to match the local environment.
- Running `config-import` twice is a recommended approach. Certain configuration can require a fully completed config import before it is respected, especially with `config_split`.
- Use a database update hook to install or uninstall modules and do not rely on the config-import of the `core.extension.yml` to handle these.
- Use `hook_deploy_NAME()` for operations that depend on config import having completed (e.g., permissions for new content types, entity operations tied to new bundles).
- If you need to allow deletion of specific config (e.g., for module uninstalls or legacy cleanup), add the appropriate prefix to the allow-list in `settings.php` as described in the [hs_config_partial module README](../docroot/modules/humsci/hs_config_partial/README.md).


### Export config_split Changes

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

1. **Enable the desired split (e.g., dev):**
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

1. **Make your changes:**
	 - Apply the desired configuration changes in the UI or via code.

1. **Export configuration:**
	 - Export config:
		 ```sh
		 drush config-export -y
		 ```
	 - Note: Split-specific configuration is not shown in the export output. Use `git status` to verify exported changes in split directories.

1. **Revert split overrides:**
	 - In `local.settings.php`, comment out or remove the split status overrides. This prevents accidental imports/exports using the wrong split in future work.

1. **Restore your environment:**
	 - Import configuration, install a fresh site, or pull down a fresh site as needed. Always ensure the correct splits are enabled before making further changes locally.

**Tips:**
- Always disable all splits and import default config before enabling a new split for export.
- Running config import twice helps ensure all config is fully applied, especially with config_split.
- Use `git status` to confirm exported changes, as split config may not appear in the standard export output.
