# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are the steps for getting set up.

## Prerequisites

1. [Install Lando](https://lando.dev/download/) (includes Docker Desktop)
2. Ensure you have Acquia Cloud credentials configured in `~/.acquia/` (needed for syncing databases)
3. Ensure your SSH keys are in `~/.ssh/` (needed for Acquia access)

## Quick Start

1. Copy `lando/default.lando.yml` to `.lando.yml`:

    ```bash
    cp lando/default.lando.yml .lando.yml
    ```

2. Build your containers:

    ```bash
    lando rebuild
    ```

    This will:
    - Install PHP 8.3, MySQL 8.0, Node.js 24
    - Run `composer install` and initialize BLT settings
    - Patch local settings files for Lando compatibility
    - Set up the multisite domain mapping

3. Sync a site database from Acquia:

    ```bash
    lando sync archaeology
    ```

    The site name matches the directory name under `docroot/sites/` and the entries in the `multisites` section of `blt/blt.yml`. Use `lando sites` to see all available sites.

4. Log in as admin:

    ```bash
    lando drush @archaeology.local uli
    ```

5. Visit the site at `https://archaeology.suhumsci.lndo.site`

## Domain Routing

This setup uses a **wildcard proxy** (`*.suhumsci.lndo.site`), so all multisites are automatically routable without `/etc/hosts` entries. The domain pattern is:

```
https://SITENAME.suhumsci.lndo.site
```

Where `SITENAME` is the site directory name with underscores replaced by dashes and double underscores replaced by periods. Examples:

| Site directory | Local URL |
|---|---|
| `archaeology` | `archaeology.suhumsci.lndo.site` |
| `hs_traditional` | `hs-traditional.suhumsci.lndo.site` |
| `gavin_wright__humsci` | `gavin-wright.humsci.suhumsci.lndo.site` |
| `default` (swshumsci) | `swshumsci.suhumsci.lndo.site` |

## Tooling Commands

| Command | Description |
|---|---|
| `lando sites` | List all available multisites |
| `lando sync SITE_NAME` | Sync a site's database and files from Acquia |
| `lando create-db SITE_NAME` | Create a local database for a multisite |
| `lando drush @SITE_NAME.local COMMAND` | Run a drush command against a specific site |
| `lando blt COMMAND` | Run a BLT command |
| `lando composer COMMAND` | Run composer commands |
| `lando node` / `lando npm` | Run Node.js / npm commands |

### Workflow: Working on a Site

```bash
# 1. See what sites are available
lando sites

# 2. Sync the site you want to work on
lando sync english

# 3. Log in
lando drush @english.local uli

# 4. Visit the site
# https://english.suhumsci.lndo.site

# 5. Clear cache after changes
lando drush @english.local cr

# 6. Export config changes
lando drush @english.local config-export
```

## Common Commands

* `lando drush @SITE_NAME.local uli` - Get an admin login link
* `lando drush @SITE_NAME.local cr` - Clear cache
* `lando drush @SITE_NAME.local config-export` - Export config
* `lando drush @SITE_NAME.local config-import` - Import config
* `lando info` - Check your Lando config, URLs, ports, etc.
* `docker ps` - Check that your Docker containers are running

## Troubleshooting

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `lando drush @SITE_NAME.local config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `lando composer install`.
* If starting fresh is your best plan of action, `lando destroy` will completely clear your running Lando instances.
* If `lando composer install` times out, increase the timeout: `lando composer --global config process-timeout 2000`.

### Proxy Issues

If the wildcard proxy isn't routing correctly:

1. Run `lando poweroff`
2. Remove the proxy container: `docker rm -f landoproxyhyperion5000gandalfedition_proxy_1`
3. Restart: `lando start`

### SSL Certificate

Trust the Lando development CA certificate for HTTPS to work without browser warnings:
[Trusting the CA](https://docs.lando.dev/core/v3/security.html#trusting-the-ca)

If you've trusted the certificate but still see warnings, remove the proxy container and rebuild:
```bash
docker rm -f landoproxyhyperion5000gandalfedition_proxy_1
lando rebuild
```

## Adding a New Site to Lando

Because the wildcard proxy and dynamic `sites.php` mapping handle routing automatically, adding a new site is simple:

1. Create the site directory under `docroot/sites/` with the appropriate settings files (copy from an existing site).
2. Add the site to `blt/blt.yml` under `multisites`.
3. Run `lando rebuild -y` to regenerate settings files.
4. Sync the database: `lando sync NEW_SITE_NAME`

No changes to `.lando.yml` or `/etc/hosts` are needed.

## Syncing from Staging or Dev

To sync from staging or dev instead of production:

1. In `docroot/sites/SITENAME/blt.yml`, change the `remote` value to: `SITE_NAME.stage` or `SITE_NAME.dev`.
2. Sync as usual: `lando sync SITENAME`.

## Configuration for local SimpleSAML authentication

To configure the SimpleSAML module for local SSO login:

1. Run `lando blt sws:keys`
2. Run `lando blt sbsc`
3. Edit `simplesamlphp/config/local.config.php` to match your database host/credentials.
4. Run `lando blt sbsc` again, then clear cache: `lando drush @SITE_NAME.local cr`

**Note:** SimpleSAML may throw errors on the login page after login. Clearing browser cookies for that site resolves this.

## Other Useful Links

* [Lando Drupal docs](https://docs.lando.dev/plugins/drupal/)
* [Drush configuration and aliases](../drush/README.md)
