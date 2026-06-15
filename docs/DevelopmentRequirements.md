# H&S Development Requirements

Requirements for development on the suhumsci stack.

## Local environment

A local LAMP stack or equivalent container environment is required. Supported options:
- [DDEV](https://ddev.readthedocs.io/) (recommended)
- [Lando](https://lando.dev/) (legacy)
- Bare metal LAMP

See [README.md](../README.md) for local setup instructions.

## Required tools

- **[Composer](https://getcomposer.org/)** — PHP dependency manager
- **[Node.js and npm](https://nodejs.org/)** — required to compile theme assets (`npm run theme-build`)
- **[Git](https://git-scm.com/)** — version control
- **[Acquia CLI (ACLI)](https://docs.acquia.com/acquia-cloud-platform/add-ons/acquia-cli/)** — used for Acquia Cloud operations (domain management, database operations, etc.)

## Acquia account setup

An Acquia account with SSH and API key access is required:
- Add your SSH public key to your [Acquia Cloud profile](https://accounts.acquia.com/account) and ensure it is saved in `~/.ssh/`
- Generate an Acquia API key and secret from your Acquia Cloud profile and add them to `drush/local.drush.yml` (see `README.md` for the config format)

> **Security Reminder:** Never commit `drush/local.drush.yml` or any file containing credentials or secrets to the repository.
