# CLAUDE.md

This file provides guidance for Claude Code when working in this repository.

## Documentation

When creating or updating documentation (`README.md` or anything in `docs/`), read [docs/DocumentationStandards.md](docs/DocumentationStandards.md) before making changes.

## GitHub Codespaces

The project supports GitHub Codespaces for cloud-based development. Configuration files:

- `.devcontainer/devcontainer.json` — container and VS Code configuration
- `.devcontainer/init-codespace.sh` — post-creation initialization script
- `.devcontainer/QUICKSTART.md` — user guide (non-technical)
- `docroot/sites/settings/codespaces.settings.php` — Drupal settings override
- `docs/GithubCodespaces.md` — developer guide

When maintaining Codespaces support:
- Keep `docroot/sites/settings/codespaces.settings.php` in sync with `docroot/sites/settings/ci.settings.php` for shared settings (database config, file paths, etc.)
- Update `.devcontainer/init-codespace.sh` if the site installation command changes (currently `drush sws:multisite:install --site=default`)
- The `CODESPACE_NAME` environment variable is automatically set by GitHub Codespaces and triggers inclusion of `codespaces.settings.php`

## Architecture Decision Records

Never create, edit, or suggest changes to files in `docs/architecture/decisions/`. ADRs are immutable historical records written by humans. Only review them if explicitly asked to do so.
