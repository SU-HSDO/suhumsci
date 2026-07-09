# GitHub Codespaces Workflow

> **Audience:** Written for anyone making changes through Codespaces, including non-developers with no git or command-line background. Every step below is spelled out in full. For technical details about how Codespaces is configured, see the [GitHub Codespaces](GithubCodespaces.md) developer guide.

> **Warning:** The terminal in your codespace has real access, not a sandbox limited to the steps in this guide. Some actions permanently destroy work, including work someone else has done, for example deleting or rebasing a branch. If you want to avoid any risk to an existing branch, create your own branch and pull request instead of pushing to one that already exists. See [Create Your Own Branch and Pull Request](#create-your-own-branch-and-pull-request). If you want to do something beyond what's covered here, check with the development team first.

## Launch a Codespace

Most codespaces are launched to tweak configuration on an existing pull request:

1. Open the pull request on GitHub.
1. Click the "Code" button.
1. Select the "Codespaces" tab.
1. Click "Create codespace on <BRANCH_NAME>". This uses the pull request's branch, so your codespace starts with the developer's changes already applied.

> **Note:** To start brand-new work instead of tweaking an existing pull request, launch the codespace from the repository's main page (not a pull request) using the current development branch, for example `12.x`. Either way, see [Create Your Own Branch and Pull Request](#create-your-own-branch-and-pull-request).

This document opens automatically in a rendered preview tab once the codespace loads. The environment starts in a few minutes. Once initialization completes, the terminal displays the site URL and an admin login link. If your browser supports pop-ups from GitHub, the login link opens automatically; otherwise copy it from the terminal and paste it into your browser.

> **Note:** The admin login link expires after a short time. If it stops working, generate a fresh one by running `drush @default.local uli` in the terminal.

> **Note:** The site you see in a codespace is a single generic site, not one of the platform's individual department sites. Configuration changes you export here still apply correctly regardless of which site a pull request is about.

## Make Configuration Changes

Log in using the admin login link, then make your changes in the site's admin UI. When finished, export the configuration and push it:

```bash
drush @default.local config:export -y
git add .
git commit -m "<DESCRIPTION_OF_CHANGE>"

# Example:
git commit -m "Update homepage banner text"

git push
```

This pushes directly to the branch the codespace was created on. If you launched from an existing pull request, your changes are added to that pull request automatically.

> **Tip:** Pushing directly to the pull request's branch is fine for a typical config tweak. If your change is substantial, or you are not sure whether the developer is still actively working on that branch, check with them first, or create your own branch and pull request instead (see below).

### Create Your Own Branch and Pull Request

Use this if you are starting brand-new work from the current development branch, or if you would rather not push directly to an existing branch. Create your own branch before making changes:

```bash
git checkout -b <BRANCH_NAME>

# Example:
git checkout -b HSD8-1234--update-homepage-banner
```

Make your changes, then export, commit, and push:

```bash
drush @default.local config:export -y
git add .
git commit -m "<DESCRIPTION_OF_CHANGE>"

# Example:
git commit -m "Update homepage banner text"

git push -u origin <BRANCH_NAME>
```

Go to GitHub in your browser. You will see a prompt to create a Pull Request. Click it and add a description. If you branched off an existing pull request's branch rather than the development branch, set the new pull request's base to that branch instead of the default.

## Stop and Restart

Your site persists across stops and restarts. To stop:

1. Click the Codespaces menu (bottom left).
1. Click "Stop current Codespace".

To restart:

1. Click the Codespaces menu.
1. Click "Manage Codespaces".
1. Find your codespace and click "Resume".

> **Note:** When you delete a codespace, the database is deleted. Configuration changes already committed to git are preserved.

## Troubleshooting

- **CSS or JS looks broken or unstyled:** Run `drush @default.local cr` to rebuild the cache.
- **Login link does not work:** Generate a fresh one with `drush @default.local uli`.
- **Anything else:** See the [GitHub Codespaces](GithubCodespaces.md) developer guide.
