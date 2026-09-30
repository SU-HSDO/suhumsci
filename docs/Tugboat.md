# Tugboat Previews

Tugboat builds a preview environment for every pull request so the H&S web team can review changes before they merge. Each preview runs a set of sites with sanitized copies of their production databases. The build is defined in `.tugboat/config.yml`. Repository settings, such as the base preview and automatic rebuilds, are managed in the Tugboat dashboard.

## Build Stages

`.tugboat/config.yml` defines three stages of commands. Which stages run depends on how the preview is built.

| Stage | What it does | When it runs |
|---|---|---|
| `init` | Installs Node.js, links the docroot, runs `composer install`, and generates settings files | Only on a preview built from scratch |
| `update` | Syncs and sanitizes each site's production database, enables `stage_file_proxy`, and creates the preview admin user | On a preview built from scratch, and on every refresh |
| `build` | Runs `composer install`, then `drush cr` and `drush deploy` on each site, then `npm run theme-build` | On every build |

## Base Preview

The repository base preview tracks the current `<major>.x` branch (see [Branching Strategy](BranchingStrategy.md)). It is the only preview that syncs databases from production.

- Tugboat refreshes the base preview on the schedule set in the repository settings. A refresh runs `update` and `build`.
- Pull request previews are built from the base preview and run only the `build` stage, including on every push. They do not sync from production.
- "Rebuild Orphaned Previews Automatically" and "Rebuild Stale Previews Automatically" are turned off, so refreshing the base preview does not rebuild existing pull request previews.
- A preview built from the base preview stores only its difference from the base preview. All previews count toward the project's storage quota. If the project reaches its quota, rebuild stale pull request previews or delete unused ones.

## Merge Behavior

Before building a pull request preview, Tugboat merges the pull request's destination branch into it. This happens on every build and cannot be turned off for automatic pull request previews. A merge conflict with the destination branch fails the build before any commands run.

> **Important:** Resolve merge conflicts with the destination branch before pushing to a pull request. Lock file conflicts (`composer.lock`, `package-lock.json`) are common after the automated dependency update pull request merges.

## Files

Previews do not copy site files. `stage_file_proxy` downloads each public file from the production site the first time it is requested. The origin URL is set by `hs_config_overrides` from the site's `xmlsitemap_base_url` state value.

The module is not part of any config split used by Tugboat, so `docroot/sites/settings/ci.settings.php` lists it in `$settings['config_exclude_modules']` to keep `drush deploy` from uninstalling it.

## Visual Diffs

After a pull request preview builds, Tugboat screenshots the pages listed under the `urls` key in `.tugboat/config.yml` and compares them pixel by pixel against the same pages on the base preview. URLs are grouped by site alias. To cover a new page, add its relative path under the appropriate alias. See [Configure Visual Diffs](https://docs.tugboatqa.com/visual-diffs/configure-visual-diffs/) in the Tugboat documentation.

## Refresh or Build From Scratch

A preview's Actions menu in the Tugboat dashboard offers two options. On a pull request preview, **Rebuild** runs only `build`. **Refresh** runs `update` and `build`, which syncs fresh production databases and takes significantly longer. Refresh a pull request preview when it needs fresh production data or when the pull request changes the `update` stage in `.tugboat/config.yml`.

To also run `init` on a new container, delete the preview, then build a new one from the pull request and choose "Build with no base preview" from the Build Preview drop-down. Do this when the pull request changes the `init` stage, or when it upgrades Drupal core to a new major version or makes other large dependency changes that fail against the existing `vendor` directory. See [Change or Update Previews](https://docs.tugboatqa.com/building-a-preview/administer-previews/change-or-update-previews/) in the Tugboat documentation.
