# GitHub Codespaces

GitHub Codespaces provides a cloud-based development environment for this project with all tools and dependencies pre-configured.

## What is Codespaces?

Codespaces is a containerized development environment running in GitHub's cloud infrastructure. You access it through your browser with VS Code built in.

## Why Use Codespaces?

Codespaces enables developers and non-developers to work on the site without installing and configuring local tools. The environment is ready to use immediately. No DDEV, Lando, or local setup required.

## How It Works

When you create a codespace:

1. A container starts with the configuration from `.devcontainer/devcontainer.json`
2. The initialization script `.devcontainer/init-codespace.sh` runs automatically
3. The default site installs and is ready to use
4. An admin login link is displayed in the terminal

## Launch a Codespace

1. Go to the GitHub repository
2. Click the green "Code" button
3. Select the "Codespaces" tab
4. Click "Create codespace on <BRANCH_NAME>"

The environment will start in 2-3 minutes. Once initialization completes, an admin login link appears in the terminal.

## Configuration

### Container Setup

`.devcontainer/devcontainer.json` specifies the container image, port forwarding, and post-create initialization.

### Drupal Settings

`docroot/sites/settings/codespaces.settings.php` contains Codespaces-specific Drupal configuration, included automatically when `CODESPACE_NAME` environment variable is present.

### Initialization

`.devcontainer/init-codespace.sh` is the startup script that creates the database, installs Composer dependencies, generates settings, and installs the default site.

## For Users

See [.devcontainer/QUICKSTART.md](.devcontainer/QUICKSTART.md) for step-by-step instructions.

## Maintenance

When maintaining Codespaces support:

- Keep `docroot/sites/settings/codespaces.settings.php` in sync with shared settings (database config, file paths) from `docroot/sites/settings/ci.settings.php`
- Update `.devcontainer/init-codespace.sh` if the site installation workflow changes
- Do not hardcode versions in documentation. Reference the source files (composer.json, Dockerfile, etc.)
