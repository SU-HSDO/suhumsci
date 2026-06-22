# 4. Use Deploy Hooks for Post-Config-Import Operations

## Status

Accepted

## Context

On this platform, `config_ignore` prevents certain categories of config from being imported automatically on existing sites, so those changes must be applied programmatically. However, some of those programmatic operations depend on configuration that does not yet exist in active storage until config import has run. Because `hook_update_N()` runs before config import, it cannot be used for this work.

Drush provides `hook_deploy_NAME()` (in a `MODULE.deploy.php` file) to solve this. It is not a Drupal core hook. This capability has always been available as part of Drush, but consistent use of `drush deploy` across all deployment and sync processes only became the standard with the migration to sws-drush-commands documented in [ADR 0002](0002-replace-blt-with-sws-drush-commands.md). Prior to that, deploy hooks could not be relied on to run in all contexts. This ADR formalizes their adoption as the standard pattern for post-config-import work now that the infrastructure consistently supports it.

A concrete example: when introducing a new content type, the role permissions tied to it must be granted programmatically because `config_ignore` excludes them from import. Placing this in an update hook causes it to run before the content type exists, and Drupal silently discards the permissions. Placing it in a deploy hook, after config import has created the content type, works as expected.

## Decision

Use `hook_deploy_NAME()` for any operation that must run after config import has completed. Common cases include:

- Granting or revoking role permissions tied to new content types or vocabularies introduced in the same deployment.
- Any programmatic config or entity operation that depends on new configuration being present in active storage.

Deploy hook names in this project must use a purely numerical suffix (e.g. `hs_admin_deploy_10001`). While Drush allows any alphanumeric string for `NAME` and executes hooks in alphanumeric order, this project adopts numerical suffixes as a standard to keep execution order explicit, auditable, and consistent with the `hook_update_N()` naming convention.

Deploy hooks must be placed in `MODULE.deploy.php` alongside the module's `MODULE.install` file.

## Consequences

- Operations that depend on config import must not be placed in `hook_update_N()` or `hook_post_update_NAME()`.
- When running site updates manually (e.g., troubleshooting a failed deployment), `drush deploy:hook` must be run explicitly after `drush config:import`. Omitting it will skip deploy hooks entirely.
- Numerical naming of deploy hooks must be maintained as a project convention. New deploy hooks should continue the sequence within their module.
- `hook_deploy_NAME()` is a Drush convention, not part of Drupal core. `hook_update_N()` and config import are standard Drupal operations available through any execution path, including the web UI (`/update.php`). Deploy hooks only run when Drush is the executor. On this platform all update paths go through Drush, so this is not a practical concern, but any future tooling that bypasses Drush would also bypass deploy hooks.
