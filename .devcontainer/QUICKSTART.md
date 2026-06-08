# GitHub Codespaces Quick Start

You are in a cloud-based development environment with the site ready to use. This guide covers the basic workflow.

## Access the Site

After initialization completes, the terminal displays:

- Site URL: the link to access the site
- Admin login link: pre-authenticated link to log in as an administrator

Click the admin login link to access the site.

## Make Configuration Changes

1. Log into the site as admin
2. Make your configuration changes in the admin interface
3. Export the changes:

```bash
drush @default.local config:export
```

Configuration is written to the `config/` directory.

## Commit and Push Changes

View what changed:

```bash
git status
git diff config/
```

Create a branch for your work:

```bash
git checkout -b <BRANCH_NAME>

# Example:
git checkout -b my-config-updates
```

Commit your changes:

```bash
git add config/
git commit -m "Description of configuration changes"
```

Push your branch:

```bash
git push -u origin <BRANCH_NAME>
```

Go to GitHub in your browser. You will see a prompt to create a Pull Request. Click it and add a description.

## Stop and Restart

Your site persists across stops and restarts. To stop:

1. Click the Codespaces menu (bottom left)
2. Click "Stop current Codespace"

To restart:

1. Click the Codespaces menu
2. Click "Manage Codespaces"
3. Find your codespace and click "Resume"

> **Note:** When you delete a codespace, the database is deleted. Your configuration changes in git are preserved.

## Troubleshooting

If setup failed or the site is not accessible, see the [GitHub Codespaces developer guide](../docs/GithubCodespaces.md) for configuration details and troubleshooting steps.
