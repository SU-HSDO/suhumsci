# Drupal Core Upgrade Process

This document outlines the recommended process for performing minor and major Drupal core upgrades for this application (e.g., 11.2 to 11.3, or 11.x to 12.0). Patch releases are handled as part of [Dependency Update Review](DependencyUpdateReview.md) and are not covered here. Major upgrades involve additional work: expect more thorough contrib and custom code review, and possible manual intervention for deprecated APIs or modules.

> **Important:** Throughout this process, keep notes on anything worth surfacing to the H&S web team (new features, permission changes, deprecated modules, behavioral shifts relevant to editors and site owners) and anything that belongs in a follow-up ticket rather than this upgrade. See [Share Callouts With the H&S Web Team Early](#share-callouts-with-the-hs-web-team-early) and [File Follow-Up Tickets](#file-follow-up-tickets) near the end of this process for what to do with those notes.

---

## Preparation and Branching

- Create a descriptive upgrade branch off the current `<major>.x` branch:

  ```bash
  git checkout <MAJOR>.x && git fetch && git pull && composer install
  git checkout -b <BRANCH_NAME>

  # Example:
  git checkout 12.x && git fetch && git pull && composer install
  git checkout -b drupal-11.3-upgrade
  ```

## Install and Configure the Site Before Upgrading

Install the site profile on your current codebase before making any upgrade changes, so you have a clean baseline to compare against:

```bash
drush @default.local si su_humsci_profile -y
```

Import configuration (run twice; config split imports sometimes need a second pass to fully resolve):

```bash
drush @default.local ci -y && drush @default.local ci -y
```

## Skim Core Release Notes and Change Records

Reading every release note and change record between versions does not scale well for a minor version upgrade that may span a dozen or more patch releases. Use this narrower approach instead:

1. Read the release notes for the first stable release of the target minor version (e.g., 11.3.0), not every patch release. Drupal's semver policy restricts new features, API changes, and deprecations to `.0` minor releases; patch releases are bug and security fixes only, and rarely require a change record. Scroll to "Major new features" and "Deprecations and API changes."
1. Skim the [Drupal change records list](https://www.drupal.org/list-changes/drupal), filtered by "Introduced in version" for the target minor version. Skip any change record for a subsystem the site doesn't use.
1. Skim the release notes for the specific patch release you're upgrading to (e.g., 11.3.13), and check whether it's flagged as a security release. If so, note the advisory number in case it requires manual configuration.
1. Briefly skim the remaining patch release notes between `.0` and your target patch version for anything unusual (a notable regression, an unusually large backport). Most patch releases only bundle backported bug and security fixes already present in the prior minor version's supported branch.
1. Document callouts as you find them, and separate them into three categories: technical blockers that must be resolved as part of this upgrade, changes worth communicating to the H&S web team or site owners, and future maintenance work that belongs in its own ticket. Keep these categories in mind through the rest of the process below; they inform what you communicate afterward and what you file for follow-up.

> **Tip:** Static analysis (PHPStan, PHPCS, and Drupal Rector, covered later in this process) reliably catches deprecated functions, classes, and changed method signatures. Spend your manual skim looking for what those tools can't catch: admin UI/UX changes relevant to editors, behavioral shifts that don't throw errors (a changed default, a hook no longer firing in the same context), and render array or form structure changes that could silently affect a `hook_form_alter()` or similar customization.

## Review Contrib Module Compatibility

Reviewing contrib separately from core release notes gives a clearer picture of what's actually forced by the core upgrade versus what's simply available.

> **Note:** This section covers identifying which contrib modules are affected by the core version change. Once you've decided which modules to upgrade, review them the same way you would an automated dependency update; see [Dependency Update Review](DependencyUpdateReview.md) for how to review release notes, diffs, and update hooks.

### Identify Modules Blocked by the Current Core Constraint

Update the core packages' constraints without running the update, so you can dry-run against the new constraint:

```bash
composer require drupal/core:~<MAJOR>.<MINOR>.0 drupal/core-recommended:~<MAJOR>.<MINOR>.0 drupal/core-composer-scaffold:~<MAJOR>.<MINOR>.0 --no-update
composer require drupal/core-dev:~<MAJOR>.<MINOR>.0 --no-update --dev

composer update -W --dry-run

# Example:
composer require drupal/core:~11.3.0 drupal/core-recommended:~11.3.0 drupal/core-composer-scaffold:~11.3.0 --no-update
composer require drupal/core-dev:~11.3.0 --no-update --dev

composer update -W --dry-run
```

Any package that fails to resolve at this point is a genuine gatekeeper: something currently constrained to a version that doesn't support the new core constraint. Resolve each conflict by loosening or bumping that package's constraint with `composer require <PACKAGE>:<CONSTRAINT> --no-update`, then re-run the dry run until it resolves cleanly.

### Identify Modules With Available Major Versions

Once the dry run resolves cleanly, get a full picture of what else is outdated, independent of what the core constraint change forced:

```bash
composer outdated --direct
```

A module appearing in the dry run's update list is not necessarily blocked by the new core version. It may simply have a newer release available since the codebase was last updated. Cross-reference against `composer outdated --direct` to distinguish "blocked by core, now unblocked" from "just outdated."

For each module with an available major version, check:

1. Whether the new major version is the first release requiring the new core minor version as a minimum (a strong signal it was blocked by core, not just due for a routine bump).
1. The module's release notes and the code diff between the current and target version, looking for schema changes, update hooks, and breaking changes to APIs the codebase uses.
1. Whether custom code depends on the module directly (`composer depends <PACKAGE>`) before assuming it's safe to leave unreviewed.

Set constraints for modules you decide to upgrade the same way as above (`composer require <PACKAGE>:<CONSTRAINT> --no-update`), re-running the dry run after each change to confirm it still resolves.

> **Important:** Not every module with an available major version belongs in this upgrade. If a module needs significant discovery (a rewritten architecture, missing upgrade documentation, heavy custom code integration), defer it to its own ticket rather than expanding the scope of the core upgrade. Document deferred modules for follow-up.

## Update Composer Constraints and Run the Update

By this point, all constraint changes (core, blocked contrib modules, and any modules you decided to upgrade) should already be staged via `composer require --no-update`, confirmed clean via repeated `composer update -W --dry-run` passes. Commit the `composer.json` changes, then run the real update:

```bash
composer update -W
```

Review the output for patch failures, dependency conflicts, or unexpected downgrades.

Use these commands to investigate any remaining dependency issues:

```bash
composer why <PACKAGE>
composer why-not <PACKAGE> <VERSION>
composer show <PACKAGE> --tree
```

> **Note:** Composer updates from automated dependency review continue running against the base branch while a core upgrade branch is in progress and may pick up module updates already applied on the upgrade branch. Expect to resolve these conflicts when the upgrade branch is finally merged.

## Handle Patch Failures

If a patch fails to apply after the update, check whether the linked drupal.org issue or GitLab merge request has a newer patch reroll targeting the new core version. Download and swap in the updated patch following [Patching](Patching.md), removing the old patch file with `git rm`.

## Run Database Updates and Export Configuration

```bash
drush @default.local updatedb -y
drush @default.local config-export -y
```

Review the exported configuration diff closely. Unexpected configuration changes are a strong signal that a core or contrib subsystem changed its defaults or schema, and point to where you should look next to confirm nothing broke. No changes at all is also worth noting, since it means the update didn't touch anything covered by exported config, which is a good sign for a clean upgrade.

> **Note:** Check whether any changed configuration is covered by `config_ignore`. See [Configuration Management](Config.md) for how the local config split and `config_ignore` interact with imports and exports.

## Run Code Scans

```bash
vendor/bin/phpstan
vendor/bin/phpcs
```

Review PHPStan output for deprecations and actionable errors introduced by the new core version. Some warnings (e.g., unsafe usage of `new static`) are Drupal conventions and can be added to PHPStan's `ignoreErrors` configuration rather than fixed.

### Run Drupal Rector

[Drupal Rector](https://www.drupal.org/project/rector) automates fixes for many deprecations PHPStan surfaces (deprecated function calls, changed constants, outdated type hints). If `rector.php` isn't already configured in the repository root, add it with the module's paths and a target core version.

Run a dry run first to review what it intends to change:

```bash
vendor/bin/rector process --dry-run
```

Then apply the fixes and re-run PHPStan and PHPCS to confirm they're clean:

```bash
vendor/bin/rector process
vendor/bin/phpstan
vendor/bin/phpcs
```

> **Important:** Review Rector's fixes manually before committing. Rector applies safe, one-line fixes, which can be less efficient than the idiomatic fix when the same deprecated call appears multiple times in the same scope (for example, loading a service repeatedly instead of once into a local variable). This doesn't make Rector's fix wrong, just worth a follow-up cleanup pass.

### Optional: Compare Against a Stricter PHPStan Baseline

To confirm the upgrade didn't introduce anything new that a stricter analysis level would catch, generate a baseline against the pre-upgrade branch and compare. `--generate-baseline` overwrites the repository's existing tracked `phpstan-baseline.neon`; this is a throwaway comparison baseline, not a replacement for the real one, so restore it afterward instead of committing it.

Generate a level 8 baseline on the pre-upgrade branch:

```bash
git checkout <MAJOR>.x && git fetch && git pull && composer install
vendor/bin/phpstan --level=8 --generate-baseline
```

Switch back to the upgrade branch and run level 8 against that baseline:

```bash
git checkout <BRANCH_NAME> && composer install
vendor/bin/phpstan --level=8
```

Any errors reported here are new on the upgrade branch and worth reviewing, even if they aren't blocking. When you're done, discard the baseline file so it doesn't get committed:

```bash
git checkout -- phpstan-baseline.neon
```

## Quality Assurance and Status Review

Review the default local site for errors and warnings:

- `/admin/reports/status` (status report)
- `/admin/reports/updates` (available updates)
- `/admin/reports/dblog` (log messages)

For each warning, decide whether it's actionable or can be safely ignored, and compare against the current status report on staging or production to rule out local-only environment issues.

> **Note:** Expect `/admin/reports/updates` to mostly repeat what you already found during contrib module review. Still worth a quick check in case anything was missed, but don't expect new information here.

> **Note:** This manual pass and the test suite (see [Run the Test Suite](#run-the-test-suite) below) catch different things. Some issues surface here first; others only show up once the test suite runs.

## Site Sync and Additional QA

Sync a live site locally and review status and logs for upgrade issues:

```bash
drush drupal:sync --site=<SITENAME>
```

Log in, click through key pages and admin routes, and review `/admin/reports/dblog` for errors. This does not need to be an exhaustive QA pass; additional QA happens once the upgrade reaches the dev or staging environment.

## Run the Test Suite

Run the PHPUnit and Codeception test suites locally rather than waiting for CI to surface failures. A core upgrade can shift test behavior in ways unrelated to your own code changes (a new minimum PHPUnit version, a changed core API a test happens to exercise), so budget time to investigate and resolve failures as part of the upgrade rather than treating a green CI run as a given.

```bash
drush sws:source:tests:phpunit
```

See [Codeception](Codeception.md) for running the Codeception suite, which also runs in CI.

## Share Callouts With the H&S Web Team Early

With the notes gathered during the process, prepare a single, comprehensive document covering everything worth surfacing to the H&S web team. Share it once the upgrade is ready to go out to dev or staging, so they have it in hand for review before it reaches that environment rather than alongside it.

> **Note:** The H&S web team are experienced Drupal site builders and editors, not developers. Keep that in mind as you write each callout, favoring what changes for them (a new permission to consider granting, a UI change editors will notice). Technical detail and implementation specifics are still worth including when they help make the point.

## File Follow-Up Tickets

With the notes gathered during the process, file anything that isn't required for this upgrade to succeed as its own ticket rather than expanding the scope of the upgrade branch. Common examples include deferred major-version contrib upgrades, deprecated function refactors that aren't yet mandatory, and unrelated module removals.

Before filing a new ticket, check the issue tracker for an existing ticket covering the same module or issue. Contrib upgrades and status report warnings are often already known and scoped from a previous upgrade; if one exists, add your findings to it instead of creating a duplicate.

Share the resulting list of follow-up tickets with the H&S web team as well, so they have visibility into what's being deferred and why, not just what's changing in this release.

## Commit, Push, and Open a Pull Request

Commit all changes and push to the remote branch. Open a pull request for review and automated testing.

## Post-Upgrade Cleanup

- Remove deprecated modules and code from the codebase.
- Restore or clean up any configuration files that no longer need local overrides.

## Troubleshooting

- If a module is not compatible, check for newer releases, alternative modules, or open issues with a workaround or patch.
- For patch failures, check the linked issue or merge request for an updated reroll targeting the new core version.
- Use `composer why`, `composer why-not`, and `composer show --tree` to investigate dependency conflicts.
- Compare status report warnings between local and production to rule out local-only environment issues before treating something as a regression.
- Document any manual steps, patches, or workarounds directly in the relevant ticket for future reference.

## Additional Notes

- For major upgrades, expect additional work including custom code review, more extensive testing, and possible manual intervention for deprecated APIs or modules.
- Keep local, staging, and production environments in sync regarding PHP and Composer versions.
- Update this document as best practices evolve or new upgrade scenarios arise.
