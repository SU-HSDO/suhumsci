# AGENTS.md

Guidance for AI coding agents (and humans pairing with them) working in this repository. This file is the source of truth for AI-assisted development; `CLAUDE.md` points here.

## Project Context

- HSDP (Humanities and Sciences Drupal Platform): a Drupal 11 multisite platform running 130+ sites on Acquia Cloud.
- Default development branch: the current `<major>.x` branch (e.g. `12.x`). Production branch: `main`. See [docs/BranchingStrategy.md](docs/BranchingStrategy.md).
- Drush toolchain is SWSDC (SWS Drush Commands), prefixed `drush sws:`. Custom commands are also namespaced `humsci:` and `drupal:`. Do not add BLT references.
- This is a public repository. Never commit credentials or internal URLs.

## Where Code Lives

> **Important:** Custom code is not only in `docroot/modules/custom`. This repo has three custom-code locations, and there is no `web/` directory.

| What | Where |
|---|---|
| Primary custom modules (`hs_*`) | `docroot/modules/humsci/` |
| Secondary custom modules (`stanford_*`) | `docroot/modules/custom/` |
| Profile submodules | `docroot/profiles/humsci/su_humsci_profile/modules/` |
| Custom themes | `docroot/themes/humsci/` (`humsci_basic` base; `humsci_colorful`, `humsci_traditional`, `humsci_airy` subthemes; `su_humsci_gin_admin` admin theme) |
| Global config (sync) | `config/default/` |
| Environment splits | `config/envs/` |
| Site-specific settings | `docroot/sites/<SITENAME>/` |
| Repo Drush commands | `drush/Commands/` |
| Vendored Drush commands | `drush/Commands/contrib/sws-drush-commands/` |
| Patches | `patches/core/`, `patches/contrib/` |
| PHPUnit config | `tests/phpunit/` |
| Codeception tests | `tests/codeception/` |
| Preact islands (select combobox) | `docroot/profiles/humsci/su_humsci_profile/js/select-lists/` |

## Module Ownership by Domain

Extend the module that owns a topic rather than creating a new one or duplicating logic. New custom modules go in `docroot/modules/humsci/` with the `hs_` prefix.

- Content types and entities: `hs_basic_page`, `hs_person`, `hs_news`, `hs_events`, `hs_event_series`, `hs_research`, `hs_publications`, `hs_cmap`, `hs_courses`
- Paragraphs and layout: `hs_paragraph_types`, `hs_layouts`, `hs_blocks`
- Config stack: `hs_config_partial`, `hs_config_prefix`, `hs_config_readonly`, `hs_config_overrides`
- Admin and Drush: `hs_admin`, `hs_dashboard`, `hs_editorial`, `hs_masquerade`, `hs_role_description`
- Integrations: `hs_siteimprove`, `hs_capx`, `hs_migrate`
- Field and views helpers: `hs_field_helpers`, `hs_views_helper`, `hs_entities`, `hs_table_filter`
- `docroot/modules/custom/`: `stanford_fields`, `stanford_media`, `stanford_migrate`, `stanford_samlauth`, `ckeditor5_plugins`
- Profile submodules: `humsci_default_content`, `humsci_events_listeners`

## How Changes Are Made

How you change code depends on which location it lives in. Check this before editing.

- `hs_*` modules (`docroot/modules/humsci/`): Owned by this repo. Edit in place and commit directly here.
- `stanford_*` modules and `ckeditor5_plugins` (`docroot/modules/custom/`): Composer-installed from upstream `su-sws/*` repositories. This directory is gitignored, so direct edits are overwritten on the next `composer install`. To change one of these modules, fork the upstream repo, open a PR there, and apply the change as a local patch in this repo via `composer.json` until it merges upstream and ships in a release. Then remove the patch. See [docs/Patching.md](docs/Patching.md). Example: `patches/contrib/stanford_fields-localist-api-public-20260601.patch`.
- Profile submodules (`docroot/profiles/humsci/su_humsci_profile/modules/`): Owned by this repo. Edit in place.
- Custom themes (`docroot/themes/humsci/`): Owned by this repo. Edit source in `humsci_basic/src/` and rebuild. See [docs/FrontendPatterns.md](docs/FrontendPatterns.md).
- Drupal core and contrib modules: Modified only via local patches. See [docs/Patching.md](docs/Patching.md).

## Conventions

- Prefer hooks (`hook_form_alter`, `hook_preprocess_HOOK`) or services over custom systems. Do not invent services or hooks.
- Keep changes minimal and localized. Match the style of the surrounding code exactly. Do not reformat entire files or restructure unrelated code. Preserve existing comments.
- Use `services.yml` for dependency injection when needed.
- Code comments should be concise and either describe _why_ the code exists, or why some odd-looking code is needed, rather than describing what the code does.

### Configuration Stack (Non-Standard)

This platform's config stack is not stock Drupal. Read [docs/Config.md](docs/Config.md) before config work.

- Never use `drush config-import --partial`. It is deprecated and destructive here. `hs_config_partial` implements safe partial imports via the transformation pipeline.
- Run `drush ci -y && drush ci -y` (twice) when importing, especially with `config_split`.
- Partial imports never delete config. Deleting a config object (e.g. an old view) requires a database update hook.
- Do not reuse one field storage across multiple bundles. Per-bundle fields make migrations and cleanup safe.
- Do not delete deprecated fields in the same deploy that migrates to their replacements. First hide them on edit forms; remove in a follow-up release.
- New config objects (vocabularies, content types, base field overrides) need a UUID.
- Enable new modules via a database update hook, not by importing `core.extension.yml`.

### Update Hooks vs Deploy Hooks

- `hook_update_N()` (in `MODULE.install`) runs before config import. Use it for schema changes, enabling modules, and granting permissions.
- `hook_deploy_NAME()` (in `MODULE.deploy.php`) runs after config import. Use it for anything that depends on config existing (permissions for a new content type, entity operations tied to new bundles). `drush deploy` and `drush drupal:sync` both run deploy hooks automatically after config import. See [ADR 0004](docs/architecture/decisions/0004-use-deploy-hooks-for-post-config-operations.md).
- Batch large updates with `$sandbox` (batch size around 50). Cache expensive lookups (e.g. the active theme) in `$sandbox` on the first run.
- Every update and deploy hook should `return t(...)` with counts or a summary, so it lands in the deploy log.

### Patches

All core and contrib modifications go through local patches. See [docs/Patching.md](docs/Patching.md). Naming: `<PROJECT>-<ISSUE>-mr-<MR>-<YYYYMMDD>.patch`. Always download the `.diff` file (not `.patch`) from GitLab. After changing patches, run `composer update --lock` and commit `composer.json`, `composer.lock`, and the patch file.

### Profile Version

The version in `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml` on `<major>.x` must always be `<major>.x-dev`. Verify this after any merge from `main` into `<major>.x`.

## Commands

```bash
nvm install             # Install and use the correct version Node.js
npm run theme-build     # Compile Sass and JS for all humsci_basic-based themes
npm run theme-watch     # Compile and watch for changes
npm run theme-visreg    # Run Percy VRT on hs_colorful and hs_traditional
npm test                # Run Sass tests

vendor/bin/phpcs        # PHP code sniffer
vendor/bin/phpcbf       # Auto-fix PHPCS issues
vendor/bin/phpstan      # Static analysis (level set in phpstan.neon)

drush sws:source:tests:phpunit       # Run PHPUnit
drush sws:codeception                 # Run Codeception (--group=<GROUP> for one suite)
drush cr                              # Cache rebuild
drush drupal:sync --site=<SITENAME>   # Sync a site from a live environment
```

See [docs/Testing.md](docs/Testing.md), [docs/CodingStandards.md](docs/CodingStandards.md), and [docs/DevelopmentRequirements.md](docs/DevelopmentRequirements.md).

## Before You Finish (Verify)

- Hook names match the module's machine name. Services are registered in `services.yml`.
- `vendor/bin/phpcs` and `vendor/bin/phpstan` pass on changed files.
- Config changes import cleanly with `drush ci -y && drush ci -y`; any deletion has a matching update hook.
- A `drush cr` (cache rebuild) would succeed.
- Any new `docs/` file is linked from `README.md`.

## Do Not

- Do not create new modules or themes unless told to. Extend an existing `hs_*` module instead.
- Do not edit `docs/architecture/decisions/` (ADRs are immutable human-written records).
- Do not use `--partial` with config-import.
- Do not reference Slack channels, internal URLs, ticket or PR numbers, or specific tool versions in documentation.
- Do not commit `drush/local.drush.yml` or any credentials.

## When Unsure

- Ask for clarification instead of guessing.
- Prefer the simplest Drupal-native solution.
- Look for an existing example in the codebase and follow it.

## Review Patterns (Follow Preemptively)

These corrections come up repeatedly in code review. Apply them before opening a PR.

### PHP

- Guard with `hasField()` before `->get()` on any field, and check `instanceof` (e.g. `ParagraphInterface`) before using a referenced entity.
- Use strict comparison (`===`, `!==`). Watch `array_search` returning `0`, which equals `FALSE` under loose comparison.
- Use `?->first()` for nullable entity references instead of null checks.
- Prefer early returns over deep nesting. Use `if` over `switch` when every case returns.
- `break` out of loops once the condition is met.
- Add return type declarations to functions and scope helper functions properly.
- Use `t()` and routing or URL objects for translatable links, not hardcoded HTML.

### Update and Deploy Hooks

- Batch with `$sandbox`; batch size around 50.
- `return t(...)` with counts or metrics from every update.
- Grant permissions in their own update hook that runs before dependent config. The `administrator` role gets all permissions by default; do not add them explicitly.
- Do not delete field storage in the same update that migrates data off it.

### Frontend (JS and Twig)

- Always wrap behavior setup in `once('key', selector, context)`. Consider `once.remove()` for cleanup when elements may re-attach. See [docs/FrontendPatterns.md](docs/FrontendPatterns.md).
- Use `visually-hidden` for screen-reader content; never `display: none` or DOM removal to hide semantics.
- Set initial ARIA state in markup (`aria-expanded="false"`), use `aria-controls` with `clean_unique_id`, and prefer a visually-hidden `<span>` over `aria-label`.
- Do not override image `alt` text when wrapping an image in a link; put the label on the link as `aria-label` or a visually-hidden span.
- For empty-content checks on rendered fields, use `|render|striptags('<img><iframe><picture><video>')|trim` with a tag allow-list.
- Use the `clean_class` Twig filter, not manual string replacement. Use spaces (not tabs) in Twig. Remove dev comments before committing.
- Scope CSS and JS to the intended target (e.g. views tables vs WYSIWYG tables). Use lowercase hex colors. Avoid `!important`. Use the theme's breakpoint function for media queries.

### Process

- Download patches into the repo; do not reference them by URL or commit ID. Run `composer update --lock` after patch changes.
- Include the relevant JIRA or ClickUp ticket link in the PR description.
