# CLAUDE.md

This file provides guidance for Claude Code when working in this repository.

## Documentation

When creating or updating documentation (`README.md` or anything in `docs/`), read [docs/DocumentationStandards.md](docs/DocumentationStandards.md) before making changes.

## GitHub Codespaces

The project supports GitHub Codespaces for cloud-based development. Configuration files:

- `.devcontainer/devcontainer.json` specifies container and VS Code configuration
- `.devcontainer/docker-compose.yml` defines web and MySQL services
- `.devcontainer/on-create.sh` runs once on codespace creation (installs dependencies, site, and encryption key)
- `.devcontainer/post-create.sh` runs after creation and displays access information
- `.devcontainer/QUICKSTART.md` is the user guide (non-technical)
- `docs/GithubCodespaces.md` is the developer guide

When maintaining Codespaces support:
- Database config is provided by `docroot/sites/settings/ci.settings.php` (GitHub Codespaces automatically sets `CI=true`)
- Update `.devcontainer/on-create.sh` if the site installation command changes
- MySQL configuration is in `docker-compose.yml` (database name, user, password)
- The `CODESPACE_NAME` environment variable is automatically set by GitHub Codespaces and used to construct the site URL

## Architecture Decision Records

Never create, edit, or suggest changes to files in `docs/architecture/decisions/`. ADRs are immutable historical records written by humans. Only review them if explicitly asked to do so.
