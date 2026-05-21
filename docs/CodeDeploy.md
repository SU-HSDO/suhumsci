# H&S Code Release and Deployment Guide

This document outlines the release and deployment workflow for the H&S application.

## Overview
- Releases follow [SEMVER](https://semver.org/) conventions: major, minor, patch.
- Day-to-day development work merges into the current `<major>.x` branch.
- Staging should track the `<major>.x-build` artifact branch generated from the current `<major>.x` branch.
- Production releases are created by merging a release pull request into `main`.
- PRs must pass all required GitHub Actions checks.
- PR labels (`major`, `minor`, `patch`) are required to trigger automation.
- When a release pull request is merged into `main`, automation creates a deployment artifact/tag and a new GitHub release.
- The deployment artifact is a tag named `YYYY-MM-DD_VERSION` (e.g., `2026-05-21_12.1.1`).
- Manual database backups are required before production deployment.

See also: [Branching Strategy](BranchingStrategy.md)

## Requirements
*See [H&S Development Requirements](DevelopmentRequirements.md)*

## Create database backups

Running the backup step now ensures the backups are completed by the time the release is created and artifact is ready to deploy.

- Open the application in the Acquia Cloud UI.
- Log in and select the HumSci Gryphon application.
- Backup all databases on Production.
- To backup all databases:
  1. Click the icon next to the "Databases" tab for Prod.
  2. Check "Select All".
  3. Click "Continue" and then "Back Up".
- The modal may take some time to disappear as it processes after clicking the “Back Up” button. Don’t click the button more than once.

## Create a New Release
### Prep the Code Locally
```bash
git checkout main && git fetch && git pull
git checkout CURRENT_MAJOR_BRANCH && git fetch && git pull
git checkout -b RELEASE_BRANCH
composer install

# Example:
git checkout main && git fetch && git pull
git checkout 12.x && git fetch && git pull
git checkout -b 12.1.1-release
composer install
```

### Prepare for Release
- Ensure all code for the release is merged into the current major development branch before creating the release branch.
- Create a short-lived release branch from the current major development branch.
- Review code changes and determine the next version using SEMVER:
	- **Major**: Incompatible API changes (e.g., `12.0.0`). Note that this should entail creating a new `<major>.x` development branch. This is typically reserved for Drupal core major version upgrades.
	- **Minor**: New functionality, backward compatible (e.g., `12.1.0`)
	- **Patch**: Bug fixes, backward compatible (e.g., `12.1.1`)
- Use `git log` or the GitHub interface to review commits:
	```bash
	git log main..RELEASE_BRANCH --oneline
	```

### Update Version Numbers
- For major or minor releases, update the version in:
	- `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml`
- Commit and push changes.

### Create the Release PR
- Push the new release branch to GitHub.
- Open a pull request from `RELEASE_BRANCH` into `main`.
- Confirm the PR contains only the code intended for the release.
- Use a PR title that matches the target version number (e.g., `12.1.1`).

### Update the Release PR
- Set the PR title to the version (e.g., `12.1.1`).
- Apply the correct `major`, `minor`, or `patch` label. This triggers automation.
- Double-check the base branch is `main`.
- Review changes and confirm SEMVER alignment.

:warning: **Double check the PR label!** Make sure the PR label is correct based on the version (major, minor, or patch).

### Merge the Release PR
- Once all tests pass, merge the PR using a **merge commit** (not squash merge).
- Set the merge commit title/message to the version (e.g., `12.1.1`).
- Confirm the correct PR label is applied before merging.

### Back to Dev
- After the release PR is merged into `main`, keep the release branch long enough to open a second pull request back into the current `<major>.x` branch.
- Use this back-to-dev pull request to return version increments and any other release-only changes to the development branch.
- Merge the back-to-dev pull request before deleting the release branch.
- If a production hotfix is ever made directly on `main`, sync that change back to the current `<major>.x` branch separately.

### Confirm Automation
- After merging, GitHub Actions will:
	- Create a new tag and release with the version number.
	- Create a deployment artifact and push it to Acquia (tag: `YYYY-MM-DD_VERSION`).
- Confirm the new release in GitHub and artifact in Acquia Cloud UI.

### Composer Lock Diff for Release Notes
- Use the `composer-lock-diff` package to generate a table of updated dependencies for release notes and PR summary.
- Composer Lock Diff is included in this repo. You can either run the command through `/vendor/bin/composer-lock-diff` or add it to your `$PATH` in `~/.bashrc`.

	```bash
	composer-lock-diff --md --from=PREVIOUS_VERSION --to=NEW_VERSION

	# Example:
	composer-lock-diff --md --from=12.1.0 --to=12.1.1
	```
- Add the output to the GitHub release notes.
- Keep the "Full Changelog" link and paste the diff at the bottom.

### Notify Teams of upcoming deployment
- Announce in client Slack channel:
	> :launch1: A new 12.1.1 release has been created. The deployment to production will begin momentarily.
- Announce in any internal Slack channels for developers:
	> :version: A new 12.1.1 release has been created for suhumsci and the production artifact is ready to deploy.

:information_source: The release has been created and a deployment artifact has been pushed, but no deployment has been made at this point. The next step is to deploy to production, which is the true “point of no return”.

## Deploy the Release Code Artifact to Production
- Open the application in the Acquia Cloud UI.
- Log in and select the HumSci Gryphon application.

:memo: **Remember:** Before deploying to production, take a moment to verify all steps and details above. If anything feels off, pause and ask for a second opinion.

### Deploy the Artifact to Production
- In Acquia Cloud UI:
	1. Click the icon next to the "Code" tab for Prod.
	2. Select the deployment artifact tag (e.g., `tags/2026-02-21_12.1.1`).
	3. Click "Continue" and then "Switch" to start deployment.

### Staging Deployment Note
- Staging should continue to track the current `<major>.x-build` branch.
- The artifact deploy command appends `-build` to a branch name when no explicit artifact branch name is provided.
- The production release workflow does not change the staging branch.

### Monitor Deployment
- Monitor progress in Acquia Cloud UI and Slack alerts.
- Once complete, confirm all sites deployed successfully.
- Announce success in Slack channels.
- If any sites failed, follow troubleshooting steps below.
  - If there is a new site provisioned in the release, the updates will run and "fail" on this site. This is not a failure, since there is no site installed yet.

:confetti_ball: Congratulations on a successful deployment! Remember to share the good news in Slack and celebrate your accomplishment.

## Troubleshooting a Deployment

:information_source: If a site failed to update, investigate using the deployment task log in Acquia Cloud UI on the main application page. This will indicate which sites failed to update at the bottom of the log.

:handshake: For major failures or critical troubleshooting, always reach out to another developer or senior engineer. Collaboration is key, and no one is expected to solve production issues alone. Use internal communication channels to coordinate. For major failures, consider a code change or rollback (see below).

**Manual Site Updates:**

- If a site failed to update, check the deployment log in Acquia Cloud UI for errors.
- Because of our parallel deployment process, sometimes there’s a database lock that prevents an upgrade. Running updates manually will resolve this issue.

	```bash
	drush @DRUSHALIAS.prod updatedb -y
	drush @DRUSHALIAS.prod ci
	drush @DRUSHALIAS.prod cr
	```
- For major failures, coordinate with other developers. Consider a hotfix or rollback if necessary.

## Rolling Back a Deployment
:bulb: **Tip:** If you think a rollback might be needed, consult with another developer or senior engineer before proceeding, and use internal communication channels to coordinate. Rollbacks are rare and should be a shared decision. A hotfix or fixing issues in place is preferred when possible.

- To rollback:
	1. Deploy the previous code artifact to production.
	2. Cancel the deploy hook for the deployment.
	3. Restore site backups over the previous code.

## Additional Notes
- Always double-check PR labels and base branches before merging.
- Use merge commits for release PRs (not squash merges).
- Keep communication clear and timely with all stakeholders.
