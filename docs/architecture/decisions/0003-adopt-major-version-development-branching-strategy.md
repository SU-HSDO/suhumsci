# 3. Adopt a major-version development branching strategy

## Status

Accepted

## Context
The previous branching model relied on `develop` as the long-lived integration branch and on pre-created `<version>-release` branches for release preparation and staging deploys. That model created unnecessary branch churn, tied day-to-day work to release-specific branches, and made Tugboat base previews depend on a branch that did not always reflect the code path used for active development. It also required additional automation to create and maintain the next release branch after every production release.

We need a simpler model that keeps active development on the current major version, keeps staging continuously updated from that development branch, and preserves a separate production release control point.

## Decision
- Use a current major version branch, such as `12.x`, as the primary development branch and default branch.
- Use a current major version branch as the normal base branch for feature and maintenance pull requests.
- Generate and deploy a corresponding `<major>.x-build` artifact branch for staging from the current `<major>.x` branch.
- Use `main` as the production release branch.
- Create release branches from the current `<major>.x` branch when preparing a production release.
- Merge release pull requests into `main` to trigger production release automation.
- After a release pull request is merged into `main`, merge the release branch back into the current `<major>.x` branch to return version increments and any other release-only changes.
- Retire the use of long-lived `<version>-release` branches and retire automation that creates the next release branch automatically.

## Consequences
- Day-to-day development is consolidated on the current `<major>.x` branch.
- Staging can track the current `<major>.x-build` branch and stay aligned with recently merged development work.
- Production releases remain intentionally controlled through `main` rather than by every merge into the development branch.
- Tugboat base previews should follow the current `<major>.x` branch instead of `develop`.
- Release preparation now includes creating a short-lived release branch and a release pull request into `main`.
- Release completion now includes a back-to-dev step so the current `<major>.x` branch receives version increments and other release-only changes.
- Existing documentation, GitHub rulesets, branch protections, and automation must follow the `main` plus `<major>.x` branching model.
