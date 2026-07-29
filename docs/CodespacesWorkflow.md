# GitHub Codespaces Workflow

> **Audience:** Written for anyone making changes through Codespaces, including non-developers with no git or command-line background. Every step below is spelled out in full. For technical details about how Codespaces is configured, see the [GitHub Codespaces](GithubCodespaces.md) developer guide.

> **Warning:** Never push directly to a branch someone else is working on, even for a small change, unless you and the developer have agreed in advance that you will work directly on their branch. This is standard practice for developers too, not just a Codespaces precaution. Some git actions permanently destroy work, including work someone else has done, for example deleting or rebasing a branch. Without that agreement, always create your own branch and pull request instead, as described below.

## Launch a Codespace

Most codespaces are launched to tweak configuration on an existing pull request:

1. Open the pull request on GitHub.
1. Click the "Code" button.
1. Select the "Codespaces" tab.
1. Click "Create codespace on <BRANCH_NAME>". This uses the pull request's branch, so your codespace starts with the developer's changes already applied.

> **Note:** To start brand-new work instead of tweaking an existing pull request, launch the codespace from the repository's main page (not a pull request) using the current development branch, for example `12.x`. Either way, you will create your own branch before making changes. See [Create Your Own Branch and Pull Request](#create-your-own-branch-and-pull-request).

This document opens automatically in a rendered preview tab once the codespace loads. The environment takes 5 to 10 minutes to start. Once initialization completes, the site opens automatically in a new browser tab, and the terminal displays an admin login link. Copy the login link from the terminal and paste it into your browser to log in.

> **Note:** The admin login link expires after a short time. If it stops working, run `drush @default.local uli` in the terminal to generate a new one. The output uses `http://default` as a placeholder domain, which does not work if you click or paste it directly. Instead, copy everything after `default` (starting with `/user/reset/...`) and paste it onto the end of your actual site URL.

> **Note:** The site you see in a codespace is a freshly installed site, not one of the platform's individual department sites.

## Create Your Own Branch and Pull Request

Before making any changes, create your own branch, whether your codespace started from an existing pull request's branch or from the development branch.

> **Note:** If you and the developer have agreed in advance that you will work directly on their branch, skip creating a branch and pull request. Export, commit, and push as shown below, but without the `git checkout -b` step and using `git push` instead of `git push -u origin <BRANCH_NAME>`.

```bash
git checkout -b <BRANCH_NAME>

# Example:
git checkout -b HSD8-1234--update-homepage-banner
```

Log in using the admin login link, then make your changes in the site's admin UI. When finished, export the configuration, commit, and push:

```bash
drush @default.local config:export -y
git add .
git commit -m "<DESCRIPTION_OF_CHANGE>"

# Example:
git commit -m "Update homepage banner text"

git push -u origin <BRANCH_NAME>
```

Go to GitHub in your browser. You will see a prompt to create a Pull Request. Click it and add a description. Alternatively, the terminal output from `git push` includes a link for creating the pull request directly; you can use that instead.

> **Important:** If your codespace started from an existing pull request's branch, set the new pull request's base to that branch instead of the development branch (`12.x`). Otherwise, your pull request will include all of that branch's changes as if they were your own. On the "Open a pull request" page, use the "base" dropdown to select that branch before creating the pull request.

## Stop and Restart

Your site, its database, and any uncommitted changes all persist across stops and restarts, exactly as you left them. To stop:

1. Click the Codespaces menu (bottom left).
1. Click "Stop current Codespace".

To restart:

1. Click the Codespaces menu.
1. Click "Manage Codespaces".
1. Find your codespace and click "Resume".

> **Note:** When you delete a codespace, the database is deleted. Configuration changes already committed to git are preserved.

> **Important:** Resuming a codespace does not pick up changes made to the branch elsewhere in the meantime, for example new commits from a developer. If you know the branch has moved on since you last used this codespace, create a new codespace on that branch instead of resuming this one.

> **Note:** Codespaces usage counts against your personal GitHub account's free monthly usage limit, not against a shared or team budget. If you use up that limit, GitHub blocks you from creating or resuming codespaces until the limit resets the next month, unless you have separately configured a spending limit to pay for additional usage. Stop your codespace when you are done for the day to avoid using up your limit unnecessarily.

## Troubleshooting

- **CSS or JS looks broken or unstyled:** Run `drush @default.local cr` to rebuild the cache.
- **Login link does not work:** Generate a fresh one with `drush @default.local uli`, then copy everything after `default` in the output and paste it onto the end of your actual site URL (see the note under Launch a Codespace).
- **Anything else:** See the [GitHub Codespaces](GithubCodespaces.md) developer guide.
