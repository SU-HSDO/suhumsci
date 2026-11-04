# Deprecating and Removing a Module

This guide covers permanently removing a module from the entire platform. 

Removing a module requires two separate deployments: the first to uninstall the module, and a follow-up to remove its code. Module code must be present when the uninstall runs.

The level of effort varies. Some module removals are straightforward: no site-level configuration, no `config_ignore` involvement, and the module's own uninstall handler cleans up after itself. Others require careful auditing and manual database updates. Use the sections below as a checklist and apply the steps that are relevant to the module being removed.

## Audit the Module

Before writing any code, understand what the module does and what it leaves behind.

1. Search the codebase for the module machine name to find all references. Run both commands: the first targets common file types, the second surfaces anything missed:

   ```bash
   grep -r "<MODULE_NAME>" . --include="*.php" --include="*.yml" --include="*.module" --include="*.info"
   grep -r "<MODULE_NAME>" . --exclude-dir={vendor,node_modules,.git}
   ```

   Example:
   ```bash
   grep -r "hs_page_reports" . --include="*.php" --include="*.yml" --include="*.module" --include="*.info"
   grep -r "hs_page_reports" . --exclude-dir={vendor,node_modules,.git}
   ```

1. Check if the module is listed in `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml`.

1. Review `config/default/config_ignore.settings.yml` for any ignore rules tied to this module or its configuration types. If rules exist, determine whether they need to be removed or retained as part of this work.

1. Search `config/default/` for any committed configuration files that declare the module as a dependency:

   ```bash
   grep -r "<MODULE_NAME>" config/default/
   ```

1. Audit active site databases for per-site configuration that depends on the module. Use a module-specific report where available, such as the [video embed field audit](#audit-video-embed-field-usage). The scope of the audit depends on what the module does and how widely it is configured per site. For modules that affect configuration covered by `config_ignore`, this step is particularly important.

> **Note:** Because `config_ignore` allows per-site configuration to diverge from the codebase, some sites may carry configuration tied to this module that no codebase audit can surface. This is an inherent consequence of supporting per-site customization. The audit will not always be complete, and there may be residual impact on sites that cannot be fully anticipated before removal.

### Audit Video Embed Field Usage

Run the read-only report from your local checkout before preparing the video embed modules for uninstall:

```bash
# Check one local site first.
ddev drush --uri=default humsci:reports:video-embed-field --sites=default

# After reviewing the command and approving the target, audit one production site.
ddev drush --uri=default humsci:reports:video-embed-field --env=prod --sites=default > /tmp/video-embed-field-audit.json
```

Use `--env=dev` or `--env=stage` for another Acquia environment. Remote environments require an explicit `--sites` list. Review the single-site result before expanding to an approved list, such as `--sites=default,hs_testing_sandbox`. Remote audits require working Drush aliases and SSH access. Load your SSH key locally with `ddev auth ssh` before running the approved command. The command sends the audit code from your checkout, so you can run it before deploying the command to Acquia.

Each site has a `clear`, `review`, or `error` status. The report includes active and deleted `video_embed_field` storage, bundles, deleted field instances awaiting purge, legacy media sources, and active configuration containing legacy module or field references. `entities_with_data` counts distinct entities with stored field data across all revisions, including deleted field data. Empty field storage still requires review. Module installation is reported separately and does not itself count as usage.

The command exits with a nonzero status if any site needs review or could not be audited. It stops on the first error and includes the failed site and completed sites in its JSON output. Sites missing from the report have not been audited. Review configuration references individually, including stale migration mappings and Views. Resolve every finding and rerun failed or unprocessed sites before treating the production audit as complete. Keep the report with the removal work; it contains site-specific information and should not be committed to this public repository.

The profile's video embed uninstall update repairs the known stale event migration mapping to use `field_media_oembed_video`. It stops if active or deleted legacy fields, installed dependent modules, or other legacy configuration references remain. Review those findings before rerunning updates. Keep the Composer package and module code available through the uninstall deployment; remove them in a separate deployment after verification.

## Write the Update Hook(s)

All uninstall preparation and the uninstall itself must happen in database update hooks. Do not rely on `drush config:import` of `core.extension.yml` to uninstall modules.

Manual configuration cleanup in an update hook is not always necessary. If the module has no configuration covered by `config_ignore`, and if the module's own uninstall handler removes all related configuration, a pre-uninstall cleanup hook may not be needed. The steps below apply when the audit reveals configuration that requires explicit handling.

> **Important:** On production (and all Acquia environments), `hs_config_partial` implements partial import behavior, meaning `drush config:import` will never delete configuration. Any configuration that needs to be deleted as part of a module removal must be handled in a database update hook or by the module's own uninstall process. It will not be cleaned up automatically by config import.

1. If the module provides permissions, configuration entities, or settings that need to be removed before uninstall: write a `hook_update_N` to do so:
   - Remove module-provided permissions from roles.
   - Delete or migrate configuration entities the module owns.
   - Remove the module from `dependencies.module` in any committed configuration that references it.

1. Write a subsequent `hook_update_N` (or continue in the same hook after configuration is clean) to uninstall the module. Check that the module is installed before calling uninstall to keep the hook re-entrant, and include a return value.

   ```php
   \Drupal::service('module_installer')->uninstall(['<MODULE_NAME>']);
   ```

1. Remove the module from `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml`. This determines what modules get installed on a fresh site install with the profile.

1. If configuration associated with the module needs to be deleted during `drush config:import`, add the appropriate prefix to the `hs_config_partial_allow_delete` allow-list. See [Configuration Management](Config.md) for details.

> **Note:** Removing a module from `dependencies.module` via an update hook prevents Drupal's uninstall cascade from deleting configuration that lists the module as a dependency. This matters most for configuration covered by `config_ignore`, where per-site customization can cause active config to diverge from the codebase in ways an update hook cannot fully reach. The audit step above exists precisely to surface this risk, but it will not always be complete. This is an expected limitation of the per-site customization architecture.

## Verify on Staging

Once the work is merged and deployed to staging, verify the uninstall succeeded based on what was changed during the audit and hook work. Check that the module is no longer listed as installed and that behavior on any sites identified during the audit looks correct.

## Verify After Production Deployment

After the release deploys to production, repeat the same checks on a representative production site before opening the code removal PR.

## Remove Module Code

Once the uninstall is confirmed, open a follow-up PR to remove the code. This does not need to ship in the immediately following release. The uninstalled module code causes no harm while it sits in the codebase, but removing it is good practice to eliminate tech debt.

For contrib modules:

```bash
composer remove drupal/<MODULE_NAME>
```

For custom modules: delete the module directory from `docroot/modules/humsci/`.

Also remove in this PR:
- Any composer patches for the module in `composer.json`
- Any remaining references found during the initial codebase audit
