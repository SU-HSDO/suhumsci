# DDEV Setup

If you want to use [DDEV](https://ddev.readthedocs.io/) for local development, here are some basic steps for getting set up.


1. [Install DDEV](https://ddev.readthedocs.io/) and a Docker provider such as OrbStack or Docker Desktop.
2. Trust the DDEV root certificate authority. [Trusting the CA](https://ddev.com/blog/ddev-local-trusted-https-certificates/)
3. Run `ddev blt drupal:sync --site=SITE_ALIAS` to pull down a copy of the live database and files for the site you wish to work on (alternatively [pull a db from staging or dev](#syncing-from-staging)). The `SITE_ALIAS` is the site alias and can be found in the `multisites` section of `blt/blt.yml`. In most cases, it matches the name in the local domain, with dashes replaced with underscores (`hs-traditional` → `hs_traditional`).
4. Run `ddev drush @[SITE_ALIAS].local uli` to log in as user:1 (Example: `ddev drush @music.local uli`).
5. Front-end engineers, return to the main documentation for [front-end build and watch commands](../README.md#builds).

## Common commands

* `ddev drush @[SITE_ALIAS].local uli` - Get a link for logging in as an admin user
* `docker ps` or `orbstack ps` - Check that your containers are running
* `ddev describe` - Check your DDEV config, including a list of domains, URLs, ports, etc.
* `ddev drush @[SITE_ALIAS].local cr` - clear cache
* `ddev drush @[SITE_ALIAS].local config-export` - export your local database settings
* `ddev drush @[SITE_ALIAS].local config-import` - import new database settings to your local.

## Troubleshooting

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `ddev drush config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `ddev composer install`.
* If you find yourself in a position where starting fresh is your best plan of action, `ddev delete` will completely clear your running instances for a clean start.
* If running `ddev composer install` results in a timeout while installing a dependency, the default composer timeout can be increased by running `ddev composer --global config process-timeout 2000`.

### `sed` error when Docker uses _VirtioFS_
When Docker is configured to use _VirtioFS_ for file sharing, you might get multiple errors like this when running `ddev restart`:

```
sed: preserving permissions for '/var/www/html/docroot/sites/sts/settings/sed7b9pfU': Permission denied
```
or
```
sed: couldn't open temporary file /var/www/html/docroot/sites/africanstudies/settings/sed5mM1CH: Permission denied
```

This is caused by [a bug](https://forums.docker.com/t/sed-couldnt-open-temporary-file-xyz-permission-denied-when-using-virtiofs/125473) in the `sed` command that causes incompatibilities with _VirtioFS_. It has been fixed, but the images used by DDEV don't have the latest version. To work around it, do the following:

1. Edit `.ddev/config.yaml` and comment or remove the following lines in the post-start hooks:
    ```yaml
    - exec: find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'username' => 'root'/'username' => 'db'/g" {}
    - exec: find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'password' => 'password'/'password' => 'db'/g" {}
    - exec: find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'host' => 'localhost'/'host' => 'db'/g" {}
    - exec-host: cp .ddev/ddev.sites.php docroot/sites/local.sites.php
    ```
2. After running `ddev restart`, execute the lines manually, changing the paths to match the ones on your local machine. If you're on macOS, you also need to alter the options for the `sed` command a bit:

    ```bash
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'username' => 'root'/'username' => 'db'/g" {}
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'password' => 'password'/'password' => 'db'/g" {}
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'host' => 'localhost'/'host' => 'db'/g" {}
    cp .ddev/ddev.sites.php docroot/sites/local.sites.php
    ```

## Adding a new site to DDEV
1. Copy an existing site's folder in `docroot/sites/` and rename it to the new site's name.
2. Edit the `blt.yml` file within your new site's folder with the corresponding site names. All other files within this folder use variables and don't need any modification.
3. Run `ddev restart`

## Syncing from Staging

In order to sync from a staging or dev site, you will have to do the following:

1. In `suhumsci/docroot/sites/SITENAME/blt.yml` (`SITENAME` being the site you are working with), change line 10 for remote to: `remote: hs_colorful.stage` or `remote: hs_colorful.dev`.
2. Sync the database as you normally would: `ddev blt drupal:sync --site=SITENAME`.

## Configuration for local SimpleSAML authentication

To configure the SimpleSAML module so that you stop seeing the configuration errors in Drupal from that module and also to allow you to login from the /user login page with your Stanford account. (These commands should be run from the root directory.)

1. Run `ddev blt sws:keys`
2. Run `ddev blt sbsc`
3. Go to the `/simplesamlphp/config` folder and edit the `local.config.php` file.
4. Make sure lines 10,11,12 match the information from your `.ddev/config.yaml` file for the site you are working on.
    * Example: If you are working on `sparkbox_sandbox` you will want to add `sparkbox_sandbox` in for the host and the dbname on line 10 and update the username and password below to db.
5. After you've gotten that file up to date, you need to run `ddev blt sbsc` once more and then clear your site cache with `ddev drush @[site_name] cr` and the error should be gone upon reloading.

**Notes:**

* There are still some slight bugs to work out with SimpleSAML. It will work for log in, but after logging in may throw errors on the login page. This can be resolved by clearing the browser cookies for that site.

* The command for `ddev drush @SITENAME.local uli` should still function with or without SimpleSAML configured to log in to the local site, if this is redirecting or not functioning correctly you should ensure the module is enabled or resync the configuration on your local site.

## Areas that need work

- Setup for local Codeception testing
