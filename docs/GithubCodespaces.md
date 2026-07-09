# GitHub Codespaces

GitHub Codespaces provides a cloud-based development environment for this project with all tools and dependencies pre-configured, requiring no DDEV, Lando, or local setup.

For the day-to-day workflow of launching a codespace and pushing changes, see the [GitHub Codespaces Workflow](CodespacesWorkflow.md) guide. This document covers how Codespaces support is configured and maintained.

## Why Use Codespaces?

Codespaces enables developers and non-developers to work on the site without installing and configuring local tools. The environment is ready to use immediately, including a non-developer path for making and exporting configuration changes without a local Drupal environment.

> **Important:** Codespaces only installs the `default` site, not any of the platform's individual sites. This is intentional: configuration exported from `default` writes to `config/default`, the shared configuration used across all sites, so a single generic site is sufficient regardless of which site a pull request's change is intended for. Codespaces should not be extended to install or switch between other sites.

## Configuration

### Container Setup

`.devcontainer/devcontainer.json` specifies the container image, port forwarding, and the `onCreateCommand`/`postStartCommand` hooks that drive initialization.

### Services

`.devcontainer/docker-compose.yml` defines the `web` and `mysql` services, including the database name, user, and password used during installation.

### Drush Configuration

`.devcontainer/drush.yml` configures the SWS drush commands (database host, name, user, and password) used by `drush sws:multisite:settings` and `drush sws:multisite:install` during initialization. `.devcontainer/on-create.sh` copies it to `drush/local.drush.yml` before running those commands.

### Initialization

- `.devcontainer/on-create.sh` runs once, at codespace creation. It installs Composer dependencies, generates multisite settings, installs the default site, configures Apache, and rebuilds the cache. It never runs again, including on resume, so it does not fetch new commits, run database updates, or import configuration.
- `.devcontainer/post-start.sh` runs every time the codespace starts, including on resume. It generates and displays the admin login link.

## Maintenance

When maintaining Codespaces support:

- The SWS drush commands generate site-specific settings during `drush sws:multisite:settings`. Database name follows the `<db-name>_<site-name>` pattern from `.devcontainer/drush.yml`, so `docker-compose.yml`'s `MYSQL_DATABASE` must match.
- Update `.devcontainer/on-create.sh` if the site installation command changes.
- The `CODESPACE_NAME` environment variable is set automatically by GitHub Codespaces and used to construct the site URL.
- `.devcontainer/devcontainer.json`'s `customizations.codespaces.openFiles` opens `docs/CodespacesWorkflow.md` automatically on codespace creation. `customizations.vscode.settings["workbench.editorAssociations"]` defaults all markdown files to the rendered preview editor, so it opens rendered rather than as raw source. Update the `openFiles` path if that document moves or is renamed.
- Do not hardcode versions in documentation. Reference the source files (`docker-compose.yml`, `composer.json`, etc.) instead.

## Troubleshooting

- **Site returns a 500 or 404 error:** Check that the Apache document root and virtual host configuration in `.devcontainer/on-create.sh` still match the container image's default Apache setup. This can break if the base image (`pookmish/drupal8ci` in `docker-compose.yml`) changes its Apache configuration.
- **CSS or JS looks broken or unstyled after a rebuild:** Confirm `.devcontainer/on-create.sh` still runs `drush @default.local cr` and the file-permission fix for `docroot/sites/default/files` as its last steps.
- For end-user issues (login link, basic CSS problems), see the [GitHub Codespaces Workflow](CodespacesWorkflow.md) guide.
