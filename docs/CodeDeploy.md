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

## Create Database Backups

Running backups before the release ensures they are completed by the time the artifact is ready to deploy. This step may be skipped if deployment will occur significantly later (e.g., after hours).

- Open the application in the Acquia Cloud UI.
- Log in and select the HumSci Gryphon application.
- To backup all databases on Production:
  1. Click the icon next to the “Databases” tab for Prod.
  2. Check “Select All”.
  3. Click “Continue” and then “Back Up”.
- The modal may take some time to disappear as it processes after clicking the “Back Up” button. Do not click the button more than once.

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

### Create a Deployment Ticket
- Create a new JIRA ticket for the release deployment.
- Clone a previous deployment ticket (e.g., `DEPLOY | <VERSION> <DATE>`).
- Assign to yourself and set the due date.
- Add the "Deployment" label and any other relevant labels.
- Use the format: `DEPLOY | VERSION DATE` for the ticket title.
- This ticket will track all steps of the release and deployment process.

### Prepare for Release
- Ensure all code for the release is merged into the current major development branch before creating the release branch.
- Create a short-lived release branch from the current major development branch.
- Review code changes and determine the next version using [SEMVER](https://semver.org/):
	- **Major**: Incompatible API changes (e.g., `12.0.0`). This typically entails a Drupal core major version upgrade or breaking changes.
	- **Minor**: New functionality, backward compatible (e.g., `12.1.0`)
	- **Patch**: Bug fixes, backward compatible (e.g., `12.1.1`)
- Use `git log` to compare changes between the current release and main:
	```bash
	git log ^main CURRENT_MAJOR_BRANCH --oneline
	```
- Check the [GitHub releases page](https://github.com/SU-HSDO/suhumsci/releases) to review the last release version and changelog.

### Update Version Numbers
- Update the version number in:
	- `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml`
- Commit and push changes:
	```bash
	git add . && git commit -m "Updates for release."
	git push
	```

### Create the Release PR
- Push the new release branch to GitHub.
- Open a pull request from `RELEASE_BRANCH` into `main`.
- Use a PR title that matches the target version number (e.g., `12.1.1`).
- Confirm the PR contains only the code intended for the release.

### Update the Release PR
- Set the PR title to the version (e.g., `12.1.1`).
- Apply the correct `major`, `minor`, or `patch` label. This label triggers the automation that creates the GitHub release and deployment artifact.
- Double-check the base branch is `main`.
- Update the PR description with release notes if applicable.
- Review changes and confirm SEMVER alignment.

> **Warning:** Double-check the PR label. The label must be correct (`major`, `minor`, or `patch`) — GitHub Actions will not trigger without it.

### Merge the Release PR
- Wait for all GitHub Actions tests to pass.
- Confirm the correct PR label is applied before merging.
- Merge the PR using a **merge commit** (not squash merge).
- The merge commit subject should be the version number (e.g., `12.1.1`).

### Back to Dev (Sync main into current development branch)
After the release PR is merged into `main`, create and merge a "back to dev" pull request to sync `main` back into the current `<major>.x` development branch:

1. Fetch and pull the latest `main` and current major branch:
	```bash
	git checkout main && git fetch && git pull
	git checkout CURRENT_MAJOR_BRANCH && git fetch && git pull
	```

2. Create a back-to-dev branch off the current major branch:
	```bash
	git checkout CURRENT_MAJOR_BRANCH && git checkout -b backtodev-VERSION
	
	# Example:
	git checkout 12.x && git checkout -b backtodev-12.1.1
	```

3. Merge `main` into your back-to-dev branch:
	```bash
	git merge main
	```

4. Update the version number in `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml` to the next dev version (e.g., `12.1.2-dev`):
	```bash
	git add . && git commit -m "backtodev-VERSION"
	git push
	```

5. Open a pull request from `backtodev-VERSION` into the current `<major>.x` branch.

6. Merge the back-to-dev pull request before deleting the release branch. This PR may be force-merged if necessary to bypass test checks on the development branch.

**Note:** If a production hotfix is ever made directly on `main`, sync that change back to the current `<major>.x` branch in a separate pull request.

### Confirm Automation (Release PR Merge)
When the release PR is merged into `main`, GitHub Actions automatically:
- Creates a new GitHub release with the version number.
- Creates a deployment artifact tag (format: `YYYY-MM-DD_VERSION`, e.g., `2026-06-03_12.0.0`).
- Pushes the deployment artifact to Acquia Cloud.

Verify the automation completed successfully:
- Check the [GitHub releases page](https://github.com/SU-HSDO/suhumsci/releases) for the new release.
- Verify the deployment artifact in Acquia Cloud UI.

### Generate Composer Lock Diff for Release Notes
After confirming the automation, generate a summary of updated dependencies:

- Use the `composer-lock-diff` package (included in this repo):
	```bash
	git fetch
	composer-lock-diff --md --from=PREVIOUS_VERSION --to=NEW_VERSION

	# Example:
	composer-lock-diff --md --from=11.28.2 --to=12.0.0
	```
- Add the markdown output to the GitHub release notes.
- Keep the "Full Changelog" link and paste the dependency diff at the bottom.

### Notify Teams of Release
Announce the new release in relevant Slack channels:

- **Client channel:**
	> A new 12.1.1 release has been created. The deployment to production will [begin momentarily / take place later tonight].

- **Internal developer channel:**
	> A new 12.1.1 release was created for suhumsci. The deployment to production will [take place momentarily / take place later tonight after hours]. Use the `<major>.x` branch as normal.

> **Note:** The release has been created and the deployment artifact is ready, but production deployment has not yet occurred. The next step is to deploy to production, which is the true “point of no return”.

## Deploy the Release Code Artifact to Production

> **Note:** Before deploying to production, verify all steps and details above. If anything feels off, pause and ask for a second opinion.

### Prerequisites for Deployment
- Open the application in the Acquia Cloud UI.
- Log in and select the HumSci Gryphon application.
- Verify that the deployment artifact exists (e.g., `tags/2026-06-03_12.1.1`).

### Deploy the Artifact to Production
In Acquia Cloud UI:
1. Click the icon next to the "Code" tab for Prod.
2. Select the deployment artifact tag (e.g., `2026-06-03_12.1.1`).
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

Announce the successful deployment in relevant Slack channels.

## Troubleshooting a Deployment

> **Note:** If a site failed to update, investigate using the deployment task log in Acquia Cloud UI. This log indicates which sites failed to update at the bottom.

For major failures or critical troubleshooting, reach out to another developer or senior engineer. No one is expected to solve production issues alone.

> **Note:** If a new site is provisioned in the release, it may show as "failed" during updates because the site is not yet installed. This is not a failure and can be ignored.

### Manual Site Updates

If a site failed to update:

1. Check the deployment log in Acquia Cloud UI for error details.
2. Database locks sometimes prevent updates during parallel deployment. Run updates manually:
	```bash
	drush @DRUSHALIAS.prod updatedb -y
	drush @DRUSHALIAS.prod ci
	drush @DRUSHALIAS.prod deploy:hook -y
	drush @DRUSHALIAS.prod cr
	```

	If you only need to re-run the full deploy sequence rather than step through commands individually, use:
	```bash
	drush @DRUSHALIAS.prod deploy
	```

	> **Important:** Always include `deploy:hook` after `config:import`. Deploy hooks run after config import by design and will be skipped if omitted. See [ADR 0004](architecture/decisions/0004-use-deploy-hooks-for-post-config-operations.md).
3. For major failures, coordinate with other developers and consider a hotfix or rollback.

## Rolling Back a Deployment

> **Tip:** Consult with another developer or senior engineer before proceeding with a rollback. Rollbacks are rare and should be a shared decision. A hotfix or fixing issues in place is usually preferred.

To rollback a deployment:
1. Deploy the previous code artifact to production (following the deployment steps above).
2. Cancel the deploy hook for the failed deployment.
3. Restore site backups over the previous code using Acquia Cloud UI.

## Additional Notes

- **Always double-check PR labels** before merging — the label is what triggers automation.
- **Use merge commits** for release PRs, not squash merges — this preserves the commit history.
- **Keep base branches correct** — ensure release PRs target `main` and back-to-dev PRs target the current `<major>.x` branch.
- **Communicate clearly** — update your team and the deployment ticket at each stage.
- **Read the branching strategy** — see [Branching Strategy](BranchingStrategy.md) for the full context on how branches work.
