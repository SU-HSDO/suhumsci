# DDEV Setup

If you want to use [DDEV](https://ddev.readthedocs.io/) for local development, here are some basic steps for getting set up.

1. [Install DDEV](https://ddev.readthedocs.io/) and a Docker provider such as OrbStack or Docker Desktop.
2. Trust the DDEV root certificate authority. [Trusting the CA](https://ddev.com/blog/ddev-local-trusted-https-certificates/)
3. Run `ddev blt drupal:sync --site=SITE_ALIAS` to pull down a copy of the live database and files for the site you wish to work on (alternatively [pull a db from staging or dev](#syncing-from-staging)). The `SITE_ALIAS` is the site alias and can be found in the `multisites` section of `blt/blt.yml`. In most cases, it matches the name in the local domain, with dashes replaced with underscores (`hs-traditional` → `hs_traditional`).
4. Run `ddev drush @[SITE_ALIAS].local uli` to log in as user:1 (Example: `ddev drush @music.local uli`).
5. Visit your site at `https://[site-name].ddev.site` (Example: `https://ethicsinsociety.ddev.site`)
6. Front-end engineers, return to the main documentation for [front-end build and watch commands](../README.md#builds).

## Common commands

* `ddev drush @[SITE_ALIAS].local uli` - Get a link for logging in as an admin user
* `docker ps` or `orbstack ps` - Check that your containers are running
* `ddev describe` - Check your DDEV config, including a list of domains, URLs, ports, etc.
* `ddev drush @[SITE_ALIAS].local cr` - clear cache
* `ddev drush @[SITE_ALIAS].local config-export` - export your local database settings
* `ddev drush @[SITE_ALIAS].local config-import` - import new database settings to your local.

## Troubleshooting

### SSH Authentication Issues

* **Host key verification failed**: If you encounter SSH errors when syncing databases, run `ddev auth ssh` to authenticate with remote servers. This commonly happens with new DDEV installations that haven't been authenticated yet.

### Database Issues

* **Command sql-sanitize was not found**: This error typically occurs when the target database is not properly bootstrapped or is empty. This commonly happens when: the database hasn't been synced yet or the database connection or drush aliases are misconfigured.
* **Solution**: First, ensure you have a working database by running `ddev blt drupal:sync --site=SITE_ALIAS` to pull down a copy of the live database. Also verify drush aliases are set correctly with `ddev drush @SITE_ALIAS.local status`.

### Starting Fresh

* **Complete reset**: Unlike Lando, DDEV automatically creates snapshots when you delete containers. If you want to completely start fresh without restoring from a snapshot, use `ddev delete --omit-snapshot`.

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `ddev drush config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `ddev composer install`.
* If you find yourself in a position where starting fresh is your best plan of action, `ddev delete` will completely clear your running instances for a clean start.
* If running `ddev composer install` results in a timeout while installing a dependency, the default composer timeout can be increased by running `ddev composer --global config process-timeout 2000`.

## Adding a new site to DDEV
1. Copy an existing site's folder in `docroot/sites/` and rename it to the new site's name.
2. Edit the `blt.yml` file within your new site's folder with the corresponding site names. All other files within this folder use variables and don't need any modification.
3. Run `ddev restart`

## Syncing from Staging

In order to sync from a staging or dev site, you will have to do the following:

1. In `suhumsci/docroot/sites/SITENAME/blt.yml` (`SITENAME` being the site you are working with), change line 10 for remote to: `remote: hs_colorful.stage` or `remote: hs_colorful.dev`.
2. Sync the database as you normally would: `ddev blt drupal:sync --site=SITENAME`.

## Codeception Testing

This setup provides a fresh test environment that matches the CI pipeline exactly, using BLT commands just like GitHub Actions.

**Developer-Friendly**: Temporarily modifies BLT configuration during testing, so it doesn't affect other developers using Lando, MAMP, or other systems. No additional configuration files needed.

### Quick Setup

1. **Setup fresh test environment (BLT-based, like GitHub):**
   ```bash
   ddev setup-tests
   ```
   This command:
   - Creates a fresh `drupal_default` database
   - Temporarily moves `blt/local.blt.yml` to avoid conflicts
   - Temporarily modifies `blt/ci.blt.yml` for DDEV networking (host: db, username: db, etc.)
   - Uses BLT with CI environment to generate settings and install Drupal
   - Installs Drupal with `su_humsci_profile` to `sites/default`
   - Configures Codeception for localhost
   - Restores all BLT configuration files

2. **Run Codeception tests using BLT:**
   ```bash
   ddev blt codeception --group=install --suite=acceptance
   ddev blt codeception --group=install --suite=functional
   ```

3. **Alternative: Run tests directly:**
   ```bash
   ddev codeception run acceptance --group=install
   ddev codeception run functional --group=install
   ```

### Available Commands

- **Setup fresh environment:** `ddev setup-tests`
- **Run all acceptance tests:** `ddev codeception run acceptance`
- **Run specific group:** `ddev codeception run acceptance --group=install`
- **Run with fail-fast:** `ddev codeception run acceptance --group=install --fail-fast`
- **Run functional tests:** `ddev codeception run functional`
- **Run with debug output:** `ddev codeception run acceptance --debug`

### Test Groups

- `install` - Tests that verify the site installation state
- `permissions` - Tests for user role permissions
- `content` - Tests for content-related functionality

## Areas that need work

- Enabling local SimpleSAML authentication
