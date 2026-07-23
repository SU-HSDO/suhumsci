# Module Evaluation

This document defines the criteria and approach for evaluating whether a Drupal requirement should be addressed with a core module, a community-contributed module, or a custom H&S solution. It is the companion reference to [ADR 0005](architecture/decisions/0005-establish-module-evaluation-process.md).

## Evaluation Criteria

No single criterion is a disqualifier on its own. These factors are weighed together in the context of the specific requirement and the platform.

### Quick Checklist

Use this as a starting point when evaluating a contributed module. For detail on any item, see the sections below. If the decision remains unclear, discuss it with the relevant developers, team leads, and the H&S web team.

- [ ] Does a core module or an existing contributed module already solve this? Consider it before building custom.
- [ ] Has the module opted into Drupal's Security Advisory Coverage?
- [ ] Is the module widely adopted? Check the install count on drupal.org.
- [ ] Are there active maintainers with recent commits and releases? Well-regarded contributors in the Drupal community as active maintainers are a strong positive signal.
- [ ] Is the issue queue healthy? No unresolved critical bugs or signs of abandonment?
- [ ] Does it use semantic versioning correctly, with clear release notes?
- [ ] Does it fit the requirement without significant unused features or workarounds needed?
- [ ] Is the module appropriately scoped for what you need? A module that introduces significant package overhead or does far more than required adds complexity and maintenance cost beyond the problem it solves.
- [ ] Will it integrate cleanly given the complexity and idiosyncrasies of this codebase? Configuration management is a critical area to evaluate. See [Configuration Management](Config.md).
- [ ] Considering all of the above, weigh the long-term maintenance cost. A module that passes every check still carries ongoing overhead.

### Security Coverage

Modules that have not opted into Drupal's Security Advisory Coverage do not receive coordinated vulnerability disclosure or guaranteed patching. The absence of security coverage warrants a deliberate decision to proceed. Treat it as a disqualifier unless there is a clear justification.

### Community Adoption

A module used broadly across the Drupal community has been tested in more environments and edge cases than one with a narrow install base. A low install count is not disqualifying, but raises the question of whether the module has been proven at scale.

### Active Maintainers

Active maintainers matter more than a high maintainer count. A module with many listed maintainers who are all inactive is effectively unmaintained. Look at recent commits and releases to assess real maintenance health. Developers with established reputations in the Drupal community as active maintainers are a strong positive signal.

### Issue Queue Health

Review the issue queue on drupal.org. Open critical or major bugs against the current stable release are a concern, but the more important question is whether those issues are being engaged with. Watch for issues asking whether the module is still maintained, or reporting basic functionality as broken with no response.

### Release History and Version Management

Look for consistent release history, proper semantic versioning, and release notes that describe what changed, including breaking changes. Major versions should reflect minimum Drupal core compatibility; minor and patch versions used appropriately for new features and fixes.

Frequent releases are not a negative signal. A module that has navigated multiple major Drupal version upgrades with timely, well-documented releases demonstrates a long-term maintenance commitment.

Alpha or beta modules can be used when warranted, but evaluate issue queue health and development pace before relying on one in production.

### Fit to Requirement

The narrowest module that addresses the requirement is usually the best choice. A module that solves your problem while enabling features, configuration forms, and behaviors you will never use introduces bloat: additional code paths, configuration to manage, and surface area that can interact unexpectedly with the platform. If a module almost fits but requires significant overrides or workarounds, weigh that friction against a custom solution.

### Composer Dependency Surface

Every contributed module may pull in additional Composer packages with their own release cycles, security advisories, and update overhead. Review `composer.json` and factor the full dependency surface into the total cost.

### Platform Compatibility

Before adopting a contributed module, review [Configuration Management](Config.md). HSDP's configuration stack is non-standard, and modules that require significant workarounds to function in this environment may cost more to maintain than a targeted custom solution.

## Maintenance Cost

Every contributed module is a long-term commitment. Over time it requires:

- Monitoring for security advisories and applying updates promptly
- Testing updates against the platform before deploying to production
- Verifying compatibility when Drupal core is upgraded
- Confirming the module continues to behave correctly in the HSDP configuration environment after each update
- Tracking the health of the module's issue queue and responding if maintenance activity drops

These obligations compound as the number of contributed modules grows.

## Unclear Decisions

Some situations do not resolve cleanly: a module that meets most criteria but has a known issue, a requirement with no suitable contributed solution, a module that fits the need but introduces complexity worth scrutinizing. Discuss the decision with the relevant developers, team leads, and the H&S web team before proceeding. Document the rationale, including what was considered and why.

## Contributing Back

When you encounter a bug or improvement opportunity in a contributed module, opening an issue or contributing a patch is encouraged. It is not a requirement, but it is a recognized part of working with open-source software.
