# 1. Use Core and Security-Approved Contributed Modules When Possible

## Status

Proposed

## Context
HSDP has a mix of custom modules, core modules, and contributed modules. 

Custom solutions have offered us the flexibility to tailor functionality to specific project needs; however, they can introduce potential challenges, including increased maintenance complexity and associated security considerations.

Core and security-approved contributed modules and related plugins, allow us to leverage community support and regular updates to enhance maintainability and project stability.

## Decision
We have decided to use core Drupal modules and security-approved contributed modules whenever possible, opting for custom development only when no suitable core or contributed options are available. This approach is intended to ensure better maintainability, minimize risks associated with custom code, and align with best practices within the Drupal community.

## Consequences

- **Positive:**
    - **Improved Maintainability:** Core and security-approved contributed modules generally receive regular updates and are better supported, reducing the burden on our development team.
    - **Reduced Technical Debt:** Relying on established modules minimizes the risk of introducing long-term issues inherent in custom solutions.
    - **Faster Development Times:** Leveraging existing modules accelerates development and reduces the time spent on bug fixing and refactoring custom code.
    - **Enhanced Security and Stability:** Core and security-approved contributed solutions are often tested and vetted by the community, lowering the risk of security vulnerabilities.

- **Negative:**
    - **Potential Limitations:** We may encounter scenarios where core modules or contributed modules do not fully meet specific project needs, which could limit customizability.
    - **Learning Curve:** Team members may need to acclimate to the available core modules and security-approved contributed modules, especially if they are accustomed to building custom solutions.
