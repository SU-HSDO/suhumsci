# Dependency Update Review

Automated dependency updates run on a recurring schedule and open a pull request directly against the codebase. The PR description includes a composer-lock-diff listing which packages changed and the version range for each. Generally only Drupal packages (contributed modules and `su-sws` packages) need review; other PHP dependencies rarely affect site behavior directly.

There is no separate approve or request-changes step on these PRs: the reviewer is directly responsible for fixing issues, deferring modules, and merging. Standard CI/CD practices still apply: investigate and fix any failing check before merging.

> **Important:** GitHub prevents a PR opened by GitHub Actions from triggering further PR-based Actions, to avoid circular runs. Close and reopen the PR immediately after it appears so the normal CI/CD checks run against it.

## Review Depth

Not every module in an update needs the same level of scrutiny, and there's no comprehensive checklist that could cover the full surface of Drupal core, contrib, and custom code. Look for signals that warrant a closer look, such as a major version bump, a new update hook, a config schema change, or a deprecation notice, and go deep only where those signals appear. Routine patch releases with no such signals can be reviewed quickly. This judgment comes with experience reviewing this codebase; early mistakes are expected and QA is the backstop.

## Check for Security Advisories

- Check whether any module in the update resolves a security advisory.
- If a security advisory fix is clean but another module in the same PR needs more work, split the security fix into its own commit or PR and merge it separately rather than holding it up.

## Review Release Notes for Each Module

For each module in the update, review the release notes for every version between the current version and the target version, not just the target version.

1. If the update jumps from `1.5.1` to `1.5.4`, read the release notes for `1.5.2` and `1.5.3` as well.
1. Look for specific callouts or upgrade notes in the release.
1. Check the module's issue queue for new issues opened against the version.
1. Review the merged issues linked from the release notes for anything that sounds significant.

## Review the Code Diff

Review the diff between the old and new version (the composer-lock-diff in the PR includes a link to this comparison), with an eye toward how the changes affect custom code and the site's existing usage of the module.

Update hooks (`.install`, post-update functions) and configuration schema changes deserve extra scrutiny when present, since they run automatically on deploy. Not every version bump includes them, but when one does:

1. Confirm any config schema or update hook changes are reflected in the PR's exported configuration, and vice versa.
1. Check whether the affected configuration is ignored via `config_ignore`, and if so, confirm it exported as expected.
1. Check whether the update changed whether the configuration is ignored (previously ignored configuration that now exports, or vice versa). See [Configuration Management](Config.md) for background on how these systems behave.

## Review Core Updates

The same review steps above apply to Drupal core updates. Core version constraints should only allow patch releases through this automated process; minor and major core version upgrades are planned and performed manually, not through this PR.

1. Check for changes to `default.services.yml` and determine whether the project should adopt them.
1. Check for changes to `default.settings.php` and determine whether the project should adopt them.
1. Note any settings the new core version removes or deprecates so they can be cleaned up from the project's own settings files.

## Defer a Module Instead of Blocking the Update

If a module needs more extensive review or manual work to upgrade safely, do not hold up the rest of the pull request on it.

1. Add or adjust a composer constraint to lock the module to its current version.
1. Update `composer.lock` to match, reverting it back to the version already in use.
1. Run `composer update -W` again to confirm the rest of the update still resolves cleanly with the module locked.
1. Discard any configuration changes an update hook for that module may have already applied.
1. Merge the rest of the update, and file a follow-up task documenting what the review found so the manual upgrade has context later.
