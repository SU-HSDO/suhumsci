# Module Evaluation

This document defines the criteria and approach for evaluating whether a Drupal requirement should be addressed with a core module, a community-contributed module, or a custom H&S solution. It is the companion reference to [ADR 0005](architecture/decisions/0005-establish-module-evaluation-process.md).

## Module Categories

When evaluating an option, it helps to understand where it comes from and what obligations come with it.

**Core modules** are part of Drupal itself, maintained by the Drupal project, and available without an additional Composer dependency. They benefit from core's release cycle and Security Team coverage.

**Community-contributed modules** are published on drupal.org and maintained outside of core. Modules that have opted into Drupal's Security Advisory Coverage receive coordinated vulnerability reporting and patching. The evaluation criteria below apply primarily to this category.

**SWS modules** are maintained by the SWS development team and shared across SWS products. They are not specific to HSDP and are not community-contributed modules. They occupy a middle ground: the development team is known and accessible, but they are not evaluated using drupal.org health signals. When an SWS module is being considered, evaluate its releases and changes with awareness of that context.

**H&S custom modules** are owned entirely by this team. They can be scoped precisely to the platform's needs and to the HSDP configuration environment, which is a meaningful advantage in some situations.

## Evaluation Criteria

No single criterion is a disqualifier on its own. These factors are weighed together, in the context of the specific requirement and the platform. A module that falls short on one dimension may still be the right choice; a module that passes every check may still introduce unacceptable complexity for this platform.

### Security Coverage

Modules that have not opted into Drupal's Security Advisory Coverage do not receive coordinated vulnerability disclosure or guaranteed patching. For a production platform of this scale, the absence of security coverage is a significant concern and warrants a deliberate decision to proceed. It is not an automatic disqualifier, but it should be treated as one unless there is a clear justification.

### Community Adoption

A module used broadly across the Drupal community has been tested in more environments, edge cases, and configurations than one with a narrow install base. Drupal.org project pages show install counts and usage data. A high install count is a positive signal. A low one is not disqualifying, but it does raise the question of whether the module has been proven at scale.

### Active Maintainers

The number of listed maintainers is less important than how many are actively involved. A module with many maintainers who are all inactive is effectively unmaintained. Look at recent commit and release activity to assess real maintenance health. The identity of maintainers matters too: developers with established reputations in the Drupal community are a positive signal. Seeing a well-regarded contributor as an active maintainer carries weight beyond the commit graph alone.

### Issue Queue Health

Review the module's issue queue on drupal.org. Open critical or major bugs against the current stable release are a concern, but the more important question is whether those issues are being engaged with. An issue queue where bugs are acknowledged, discussed, and resolved is healthier than one where reports sit unanswered. Watch for issues asking whether the module is still maintained, or reporting basic functionality as broken with no response. Those patterns are often more telling than the raw count of open issues.

### Release History and Version Management

A module with a consistent release history, recent releases, and proper use of semantic versioning is easier to maintain over time. Look for major versions that correctly reflect minimum Drupal core compatibility, and minor and patch versions used appropriately for new features and fixes. Release notes that clearly describe what changed, including whether any changes are breaking, reduce the risk of an update causing unexpected behavior on this platform.

Frequent releases are not a negative signal. What matters is the nature and quality of the updates. A module that has navigated multiple major Drupal version upgrades with timely, well-documented releases has demonstrated a long-term maintenance commitment.

Modules tagged as alpha or beta can be used when the situation warrants it, but a pre-stable designation exists for a reason. Evaluate the state of the issue queue and the pace of development before relying on a pre-stable module in production.

### Fit to Requirement

The best contributed module for this platform is usually the narrowest one that addresses the requirement. A module that solves your problem while enabling features, configuration forms, and behaviors you will never use introduces bloat: additional code paths to audit, additional configuration to manage, and additional surface area that can interact unexpectedly with the platform. A tighter fit reduces all of those risks. If a module almost fits but would require significant overrides or workarounds to behave as needed, weigh that friction against a custom solution.

### Composer Dependency Surface

Every contributed module may pull in additional packages via Composer. Those packages have their own release cycles, security advisories, and update overhead. A module that introduces several upstream Composer dependencies adds maintenance work that is easy to overlook at adoption time. Review the module's `composer.json` and consider the full dependency surface as part of the total cost.

### Platform Compatibility

HSDP's configuration management stack is non-standard and affects how contributed modules behave on this platform. Before adopting a contributed module, review [Configuration Management](Config.md) to understand how the platform manages configuration across 130+ sites. A module that requires significant workarounds to function correctly in this environment may cost more to maintain long-term than a targeted custom solution.

## Maintenance Cost

Every module added to this platform is a long-term commitment that is easy to underestimate at adoption time, when the module solves a visible problem. Over time it requires:

- Monitoring for security advisories and applying updates promptly
- Testing updates against the platform before deploying to production
- Verifying compatibility when Drupal core is upgraded
- Confirming the module continues to behave correctly in the HSDP configuration environment after each update
- Tracking the health of the module's issue queue and responding if maintenance activity drops

These obligations compound as the number of contributed modules grows. Evaluating long-term maintenance burden, not just immediate fit, is one of the most important parts of the decision.

## Unclear Decisions

Some situations do not resolve cleanly: a module that meets most criteria but has a known issue, a requirement with no suitable contributed solution, a module that fits the need but introduces complexity worth scrutinizing. In those cases, the decision should be discussed among the relevant developers, team leads, and the H&S web team before proceeding. Document the rationale for the choice, including concerns that were considered and why the decision went the way it did.

## Contributing Back

When you encounter a bug, limitation, or improvement opportunity in a contributed module, opening an issue or contributing a patch is encouraged. It is not a policy requirement, but it is consistent with the spirit of relying on community-maintained software and benefits the broader Drupal ecosystem. Contributions from this team do happen and are a recognized part of working with open-source software.
