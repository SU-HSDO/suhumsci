# Drupal Core Upgrade Process

This document outlines the recommended process for performing major and minor Drupal core upgrades for this application. Patch releases are handled as part of routine maintenance and are not covered here. For major upgrades (e.g., Drupal 9 → 10), expect additional work, more thorough review, and possible manual intervention for deprecated APIs or modules.

---

### Preparation and Branching

- Ensure you have a recent backup of your database and files.
- Check your local environment for required PHP and Composer versions.
- Create a descriptive upgrade branch:
	- `git checkout -b drupal-core-upgrade`

### Install and Configure Site Before Upgrading

Install the site profile on your current codebase before making any upgrade changes:

- `drush @default.local si su_humsci_profile -y`

Import configuration (run multiple times if needed for config splits):

- `drush @default.local ci -y && drush @default.local ci -y`

---

### Review Release Notes and Module Compatibility

- Review all Drupal core release notes for every version between your current version and the target version (including all patch, minor, and major releases):
	- [Drupal core releases](https://www.drupal.org/project/drupal/releases)
	- Pay special attention to breaking changes, new features, security advisories, and any changes that may affect contributed or custom modules.
	- Document any important changes or actions required for your upgrade.
- Review contributed module release notes and compatibility notes.
- Check for any required updates to custom code, third-party libraries, or site profiles.
- For modules with major version changes, review upgrade documentation and changelogs.
- Use Composer to check outdated packages:
	- `composer outdated --direct`
- For modules that are blocked by core version, note them for follow-up upgrades.
- If upgrading CKEditor or other major modules, review their release notes for breaking changes.

---

### Update Composer Constraints

- Edit `composer.json` to update Drupal core and related package constraints, e.g.:
	- `"drupal/core": "~10.5.0",`
	- `"drupal/core-composer-scaffold": "~10.5.0",`
	- `"drupal/core-recommended": "~10.5.0",`
	- For dev requirements, update `drupal/core-dev` to match the same version constraint as the other core packages.
- If upgrading PHP, update the `php` constraint as needed.
- Use Composer commands to investigate dependency issues:
	- `composer why <PACKAGE>`
	- `composer why-not <PACKAGE> <VERSION>`
	- `composer show <PACKAGE> --tree`

---

### Run Composer Update

- Run a dry run to check for conflicts:
	- `composer update -W --dry-run`
- Resolve any package conflicts, update or remove deprecated modules as needed.
- Run the actual update:
	- `composer update -W`
- Review output for patch failures, dependency issues, or unexpected downgrades/upgrades.

---

### Handle Module Upgrades and Patches

- For modules with new major versions, review upgrade notes and test compatibility.
- If a module is downgraded unexpectedly, use Composer commands to investigate and resolve conflicts.
- For modules requiring patches, check for updated patches or merge requests:
	- Download new patches from Drupal.org or GitLab, and update patch files in the codebase.
	- Apply patches and verify they succeed after update.
	- For more details on patch management, see [Patching.md](Patching.md).
- Remove obsolete or deprecated modules as recommended in release notes or status reports.
- For modules blocked by core version, document for follow-up upgrades.

---

### Run Database Updates and Export Configuration

- Update the database:
	- `drush @default.local updatedb -y`
- Export configuration:
	- `drush @default.local config-export -y`

---

### Quality Assurance and Status Review

- Review the default local site for errors and warnings:
	- `/admin/reports/status` (Status report)
	- `/admin/reports/updates` (Available updates)
	- `/admin/reports/dblog` (Log messages)
- Check for deprecated modules, missing libraries, and other warnings.
- Uninstall deprecated modules and remove from install profiles as needed.
- For all Status Report warnings, review whether they are actionable or can be ignored.
  - Compare the local status report to staging or production to identify local-only issues.

---

### Run Code Scans

- Run PHPStan for deprecation and static analysis:
	- `vendor/bin/phpstan |& tee ~/logs/suhumsci/drupal-compatibility.log`
	- Review scan results for deprecations and actionable errors.
	- Focus on deprecation issues and breaking changes.
	- Some warnings (e.g., "unsafe usage of new static") are Drupal conventions and can be ignored or added to ignoreErrors in PHPStan config.
- Run PHPCS for coding standards and PHP compatibility:
	- `vendor/bin/phpcs`
	- For PHP version compatibility:
		- `vendor/bin/phpcs -p docroot/modules --standard=PHPCompatibility --runtime-set testVersion 8.3 --extensions=php,module,install,inc`

---

### Site Sync and Additional QA

- Sync a live site locally and review status/logs for upgrade issues:
	- `drush drupal:sync --site=<SITENAME>`
- Click through key pages, review logs, and validate site functionality.
- Review log messages and test key content types, views, and admin pages.
- Additional QA will take place once the upgrade is on the staging environment. This does not need to be a thorough QA process.

---

### Commit, Push, and Open a Pull Request

- Commit all changes and push to the remote branch.
- Open a pull request for review and automated testing.

---

### Post-Upgrade Cleanup

- Remove deprecated modules and code from the codebase.
- Restore or clean up configuration files as needed.
- Document any follow-up tasks for future maintenance (e.g., modules blocked by core version, config schema updates).

---

### Troubleshooting

- If a module is not compatible, check for newer releases, alternative modules, or review open issues for patches.
- For patch failures, review the patch file, check for updated versions, or compare with merge requests.
- Use Composer commands to investigate dependency issues and resolve as needed.
- Always review release notes for breaking changes, new requirements, and module deprecations.
- For code scan warnings, focus on deprecations and breaking changes; ignore known Drupal conventions as needed.
- Compare status and warnings between local and production to identify local-only issues.
- Document any manual steps, patches, or workarounds for future reference.

---

### Additional Notes

- For major upgrades, expect additional work including custom code review, more extensive testing, and possible manual intervention for deprecated APIs or modules.
- Keep your local and production environments in sync regarding PHP and Composer versions.
- Update this document as best practices evolve or new upgrade scenarios arise.
