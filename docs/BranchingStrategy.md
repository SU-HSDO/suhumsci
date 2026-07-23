# H&S Branching Strategy

This document describes the active branching model for the HSDP application.

## Branch Roles

- `<major>.x` is the primary development branch for the current major version. Example: `12.x`.
- `<major>.x` is the default branch in GitHub and the normal base branch for feature and maintenance pull requests.
- `<major>.x-build` is the deployment artifact branch generated from `<major>.x` and deployed to staging.
- `main` is the production release branch. Only release pull requests should merge into `main`.

## Day-to-Day Development

- Branch feature and maintenance work from the current `<major>.x` branch.
- Open pull requests back into `<major>.x`.
- After updates are pushed to `<major>.x`, automation updates the `<major>.x-build` artifact branch.
- Staging should track `<major>.x-build` so merged development work is available for review quickly.

## Pull Requests

- Pull requests should be scoped to the problem they are solving. Multiple smaller Pull Requests are generally preferred.

## Release Flow

- Ensure all work intended for a release has already been merged into the current `<major>.x` branch.
- Create a short-lived release branch from `<major>.x` when you are ready to prepare a release.
- Open a pull request from that release branch into `main`.
- Apply the appropriate `major`, `minor`, or `patch` label to the release pull request.
- Merge the release pull request into `main` using a merge commit.
- When the release pull request is merged into `main`, release automation creates the GitHub release and deployable Acquia artifact tag.

## Profile Version in the Development Branch

The version number in `docroot/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml` on the current `<major>.x` branch should always reflect the development branch as `<major>.x-dev` (e.g., `12.x-dev`). If you ever merge `main` into `<major>.x` directly, verify this value remains `<major>.x-dev` afterward.

## Direct Merges to Main

All code should reach `main` through the release process described in [CodeDeploy.md](CodeDeploy.md). Direct merges to `main` that bypass `<major>.x` should be rare and treated as exceptional (for example, a critical production hotfix).

When a change is merged directly into `main`, it must also be applied to the current `<major>.x` branch. Use a squash merge when merging directly into `main`. The preferred way to bring the change into `<major>.x` is to cherry-pick the specific commit via a new branch and pull request:

1. Create a branch off the current `<major>.x` branch.
1. Cherry-pick the commit that was applied to `main`:
	```bash
	git cherry-pick <COMMIT_SHA>
	```
1. Push and open a pull request targeting the current `<major>.x` branch.

If a bulk merge from `main` into `<major>.x` is the better option in a given situation, do that through a new branch and pull request as well. In either case, verify the profile version in `su_humsci_profile.info.yml` remains `<major>.x-dev` after the merge.

## Retired Workflow Pieces

- Long-lived `<version>-release` branches are no longer used.
- Automation no longer creates the next release branch or an automatic next-release pull request.
- `develop` is no longer the default branch or the integration branch for current work.

## Operational Notes

- GitHub rulesets and branch protections should apply to `main` and `<major>.x` branches.
- Tugboat base previews should track the current `<major>.x` branch rather than `main`.
- The artifact deploy command appends `-build` to a branch name when no explicit artifact branch name is provided.
