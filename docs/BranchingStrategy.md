# H&S Branching Strategy

This document describes the active branching model for the H&S application.

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

## Retired Workflow Pieces

- Long-lived `<version>-release` branches are no longer used.
- Automation no longer creates the next release branch or an automatic next-release pull request.
- `develop` is no longer the default branch or the integration branch for current work.

## Operational Notes

- GitHub rulesets and branch protections should apply to `main` and `<major>.x` branches.
- Tugboat base previews should track the current `<major>.x` branch rather than `main`.
- The artifact deploy command appends `-build` to a branch name when no explicit artifact branch name is provided.
