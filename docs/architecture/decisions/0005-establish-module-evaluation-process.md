# 5. Establish a Module Evaluation Process for Core, Contributed, and Custom Solutions

## Status

Accepted

## Context

HSDP runs 130+ Drupal sites for Stanford's School of Humanities and Sciences on Acquia. The codebase includes a mix of H&S custom modules, SWS modules maintained by the broader SWS development team, and community-contributed modules from drupal.org.

Custom solutions offer precision and flexibility, but they introduce maintenance complexity and long-term ownership obligations specific to this team. Core and community-contributed modules benefit from broad community support and established security processes, which can reduce maintenance burden at scale. At the same time, contributed modules carry their own ongoing costs: monitoring for updates, verifying compatibility across Drupal core upgrades, and confirming they continue to behave correctly in this platform's environment.

The right choice in any situation weighs two factors: how well the approach fits the requirement and how readily it can be delivered, and what it will cost to maintain over time. A contributed module that already solves the problem has genuine value in its existence. Rebuilding something that works is rarely justified, even if a custom solution might theoretically be leaner long-term. Decisions between contributed and custom solutions have historically been made without a shared framework, leading to inconsistent outcomes and choices made without full visibility into either factor.

## Decision

Module choices (whether to use a core module, a community-contributed module, or a custom H&S solution) will be evaluated using a documented framework rather than made ad-hoc. The evaluation weighs two factors: fit to the requirement, including how readily a solution can be delivered, and long-term cost, meaning implementation effort plus ongoing maintenance.

Core and contributed modules should be considered first. When a suitable option exists that fits the requirement and the platform's environment without significant workarounds, it is often the right choice. When no suitable option exists, or when the workarounds required to make a contributed module fit would exceed the cost of a targeted custom solution, custom development is the appropriate path.

The evaluation criteria are documented in [Module Evaluation](../../ModuleEvaluation.md). When a decision is unclear or carries significant risk, it should be discussed among the relevant developers, teams, and the H&S web team before proceeding.

## Consequences

- Module decisions are guided by a shared framework, producing more consistent outcomes and documented rationale across all contributors to the platform.
- Total cost (implementation plus ongoing maintenance) is evaluated upfront rather than discovered after the fact.
- Contributed modules are considered first, but the outcome of a thorough evaluation may be custom development when that is the more maintainable long-term choice.
- Contributing improvements to upstream modules when the opportunity arises is encouraged as part of working with open-source software.
