# CLAUDE.md

This file provides guidance for Claude Code when working in this repository.

## Documentation

When creating or updating documentation (`README.md` or anything in `docs/`), read [docs/DocumentationStandards.md](docs/DocumentationStandards.md) before making changes.

## GitHub Codespaces

The project supports GitHub Codespaces for cloud-based development. Configuration files:

- `.devcontainer/devcontainer.json` specifies container and VS Code configuration
- `.devcontainer/docker-compose.yml` defines web and MySQL services
- `.devcontainer/drush.yml` configures Drupal SWS drush commands for Codespaces
- `.devcontainer/on-create.sh` runs once on codespace creation (installs dependencies and site)
- `.devcontainer/post-create.sh` runs after creation and displays access information
- `docs/CodespacesWorkflow.md` is the process guide for launching a codespace and pushing changes, written for a non-developer audience (see its `> **Audience:**` callout)
- `docs/GithubCodespaces.md` is the conceptual/reference guide covering configuration and maintenance, for developers

When maintaining Codespaces support:
- The SWS drush commands generate site-specific settings during `drush sws:multisite:settings`
- The `.devcontainer/drush.yml` file configures the database host and name for the installation process
- Update `.devcontainer/on-create.sh` if the site installation command changes
- MySQL configuration is in `docker-compose.yml` (database name, user, password)
- The `CODESPACE_NAME` environment variable is automatically set by GitHub Codespaces and used to construct the site URL in post-create.sh
- `devcontainer.json`'s `customizations.codespaces.openFiles` and `workbench.editorAssociations` open `docs/CodespacesWorkflow.md` in rendered preview on codespace creation

## Architecture Decision Records

Never create, edit, or suggest changes to files in `docs/architecture/decisions/`. ADRs are immutable historical records written by humans. Only review them if explicitly asked to do so.
