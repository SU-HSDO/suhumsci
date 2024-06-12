# 1. Compile theme assets on demand

## Status

Accepted

## Context
To improve development workflows and increase efficiency, custom theme assets can be compiled on demand and through automation instead of as part of the development workflow. Additionally, compiled theme assets do not need to be committed to the repo.

## Decision
Instead of compiling assets for themes and committing the compiled assets to the repo as part of the development process, these assets will be compiled on demand through automation and the CI/CD process.

## Consequences
- `npm` will be required by all developers to compile theme assets.
- All CI/CD and automation commands now also compile theme assets. This includes `blt deploy`, `blt drupal:sync`, and the automated tests performed by Github Actions.
- Developers will be required to compile theme assets using the `composer build-theme` command on their local machines when updating code locally and/or switching branches with theme changes to have the correct theme assets.
