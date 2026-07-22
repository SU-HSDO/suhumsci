# GitHub Codespaces

GitHub Codespaces provides a cloud-based development environment for this project with all tools and dependencies pre-configured, requiring no DDEV, Lando, or local setup.

For the day-to-day workflow of launching a codespace and pushing changes, see the [GitHub Codespaces Workflow](CodespacesWorkflow.md) guide. This document covers how Codespaces support is configured and maintained.

## Why Use Codespaces?

Codespaces enables developers and non-developers to work on the site without installing and configuring local tools. The environment is ready to use immediately, including a non-developer path for making and exporting configuration changes without a local Drupal environment.

> **Important:** Codespaces only installs the `default` site, not any of the platform's individual sites. This is intentional: configuration exported from `default` writes to `config/default`, the shared configuration used across all sites, so a single generic site is sufficient regardless of which site a pull request's change is intended for. Codespaces should not be extended to install or switch between other sites.

## Configuration

### Container Setup

`.devcontainer/devcontainer.json` specifies the container image, port forwarding, the `onCreateCommand`/`postAttachCommand` hooks that drive initialization, and the `ghcr.io/devcontainers/features/node:1` feature that installs `nvm`.

### Services

`.devcontainer/docker-compose.yml` defines the `web` and `mysql` services, including the database name, user, and password used during installation.

### Drush Configuration

`.devcontainer/drush.yml` configures the SWS drush commands (database host, name, user, and password) used by `drush sws:multisite:settings` and `drush sws:multisite:install` during initialization. `.devcontainer/on-create.sh` copies it to `drush/local.drush.yml` before running those commands.

### Initialization

- `.devcontainer/on-create.sh` runs once, at codespace creation. It sources the `nvm` installed by the node feature and runs `nvm install`/`nvm use` (reads `.nvmrc`, so it stays correct even if the feature's own pinned version drifts out of sync), installs Composer dependencies, builds theme CSS/JS (not committed to the repo), generates multisite settings, installs the default site, configures Apache, and rebuilds the cache. It never runs again, including on resume, so it does not fetch new commits, run database updates, or import configuration.
- `.devcontainer/post-attach.sh` runs every time a client attaches, including on resume. It opens the workflow guide and generates and displays the admin login link. This runs later than `postStartCommand` would, after the client is actually connected, so its output is visible rather than only landing in the creation log.

## Maintenance

When maintaining Codespaces support:

- The SWS drush commands generate site-specific settings during `drush sws:multisite:settings`. Database name follows the `<db-name>_<site-name>` pattern from `.devcontainer/drush.yml`, so `docker-compose.yml`'s `MYSQL_DATABASE` must match.
- Update `.devcontainer/on-create.sh` if the site installation command changes.
- The `CODESPACE_NAME` environment variable is set automatically by GitHub Codespaces and used to construct the site URL.
- `.devcontainer/post-attach.sh` (wired to `postAttachCommand`) opens `docs/CodespacesWorkflow.md` using the `code` CLI available inside the container. `customizations.vscode.settings["workbench.editorAssociations"]` defaults markdown files to the rendered preview editor, so it opens rendered rather than as raw source. An earlier attempt used `customizations.codespaces.openFiles` instead, but that mechanism did not consistently respect the editor association; `postAttachCommand` was used instead because it opens the file through the same code path as manually opening a file, which does respect it. Update the path in `post-attach.sh` if that document moves or is renamed.
- The `ghcr.io/devcontainers/features/node:1` feature installs `nvm` to a shared, group-writable location (`/usr/local/share/nvm`) and adds the container's actual runtime user to its group. This specifically avoids a mismatch discovered while debugging this setup: lifecycle hooks like `on-create.sh` run as root, but the interactive terminal a person actually attaches to can run as a different user, and a plain `~/.nvm` install is only usable by whichever single user it was installed for.
- Do not hardcode versions in documentation. Reference the source files (`docker-compose.yml`, `composer.json`, etc.) instead.

## Troubleshooting

- **Site returns a 500 or 404 error:** Check that the Apache document root and virtual host configuration in `.devcontainer/on-create.sh` still match the container image's default Apache setup. This can break if the base image (`pookmish/drupal8ci` in `docker-compose.yml`) changes its Apache configuration.
- **CSS or JS 404s or looks broken or unstyled:** Theme assets are not committed to the repo and must be built explicitly. Confirm `.devcontainer/on-create.sh` still sources `nvm` from `/usr/local/share/nvm` and runs `nvm install`/`nvm use` before `composer build-theme` runs, and still runs `drush @default.local cr` and the file-permission fix for `docroot/sites/default/files` as its last steps.
- For end-user issues (login link, basic CSS problems), see the [GitHub Codespaces Workflow](CodespacesWorkflow.md) guide.
