# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

_Prerequisite: Make sure you have added your SSH key in Acquia cloud, and that it's saved in your `~/.ssh folder._

1. [Install Lando](https://lando.dev/download/).
    * Apple M1/Silicon Users will need to pay special attention to the version of Lando and Docker they install for proper functionality. "If you have a new Apple Silicon based Mac then choose the arm64 DMG from Lando."
2. Copy `lando/default.lando.yml` to `.lando.yml`.
3. Take the `.loc` domains in the `.lando.yml` file and add them to your `/etc/hosts` file, as shown below:

    ```yaml
    127.0.0.1           swshumsci.suhumsci.loc
    127.0.0.1           archaeology.suhumsci.loc
    127.0.0.1           dsresearch.suhumsci.loc
    127.0.0.1           duboislab.suhumsci.loc
    127.0.0.1           economics.suhumsci.loc
    127.0.0.1           francestanford.suhumsci.loc
    127.0.0.1           insidehs.sushumsci.loc
    127.0.0.1           it-humsci.suhumsci.loc
    127.0.0.1           lowe.suhumsci.loc
    127.0.0.1           mathematics.suhumsci.loc
    127.0.0.1           mrc.suhumsci.loc
    127.0.0.1           philit.suhumsci.loc
    127.0.0.1           popstudies.suhumsci.loc
    127.0.0.1           shenlab.suhumsci.loc
    127.0.0.1           sparkbox-sandbox.suhumsci.loc
    127.0.0.1           swshumsci-sandbox.suhumsci.loc
    127.0.0.1           symsys.suhumsci.loc
    ```

4. Build your containers: `lando rebuild`
    * Note: After running `lando rebuild` you should see a list a APPSERVER URLS. A `green` URL signifies the `.loc` domain has been added to your `/ect/hosts` file. If you see a `red` URL, go back to step 3 and add the `.loc` domain to your `/ect/hosts` file.
5. Install your PHP dependencies: `lando composer install`
6. Run `lando blt blt:init:settings` and confirm that it added a `local.settings.php` file to each of your `[my-multisite]/settings` folders (ex. `/docroot/sites/default/settings/local.settings.php`).
7. Make sure the db settings in each of these `local.settings.php` files matches the settings in the `.lando.yml`. Note: the `database` service corresponds to the `default` multisite. The rest of the services have names that match their multisite. For example, for the default site, make sure that these values, and key-value pairs match:

    * (line 10): `$db_name = 'sparkbox_sandbox';`
    * 'database' => $db_name,
    * 'username' => 'drupal',
    * 'password' => 'drupal',
    * 'host' => 'database',

8. Create a new file in `/docroot/sites` called `local.sites.php`. This adds new routing based on our local environments and local `sites/` folders. Add the following code there:

    ```php
        <?php
        // This is required if you have localdev domains that are different from the
        // url structure that's already added to $sites in sites.php
        $settings = glob(__DIR__ . '/*/settings.php');
        foreach ($settings as $settings_file) {
        $site_dir = str_replace(__DIR__ . '/', '', $settings_file);
        $site_dir = str_replace('/settings.php', '', $site_dir);
        if ($site_dir == 'default') {
            $site_dir = 'swshumsci';
        }
        $sitename = str_replace('_', '-', str_replace('__', '.', $site_dir));
        $sites["$sitename.suhumsci.loc"] = $site_dir; // Do we need to add more things to our sites array, to get requests to reach our multisites?
        }
    ```

    * **Troubleshooting note:** This file is very picky of typos and spacing, if you have issues syncing, ensure this file does not have extra spaces or mistyped information.

9. Run `lando blt drupal:sync --site=sparkbox_sandbox` to pull down a copy of the database, files for the default multisite, and the custom views and blocks that have been built out.
10. Run `lando info`, and browse to the url for your multisite. For the default site, you should be able to pull up http://sparkbox-sandbox.suhumsci.loc// in your browser.
11. Depending on the local domains you've set up, you may need to add a `docroot/sites/local.sites.php` file, and use it to add your local domains to the `$sites` array. Otherwise, requests to your local multisite domains may get sent to the default site.

## Switching between local sites

1. In your `.lando.yml` file, uncomment the service for the site you want to run locally.
2. Run `lando rebuild` (this needs to be run anytime you make changes to `.lando.yml`).
3. Confirm that the password, database, and hostname values in `sites/[my-multisite]/settings/local.settings.php` correctly match the values in your `.lando.yml` file.
4. Ensure you have created your `local.sites.php` file in the `/docroot/sites` folder as indicated in the Lando steps above.
5. Sync the database and files with a copy from production: `lando blt drupal:sync --site=[my-multisite]`.
6. From now on, when you run a cache clear or try to get an admin link, you'll need to specify which Drupal site you are performing the action, for instance, for the default site: `lando drush @default.local cr` and for the economics site: `lando drush @economics.local uli`.

Troubleshooting Note for local sites: If multisite setup is causing you issues, try setting up the default setup first and then attempt a multisite configuration.

## Setup for local Codeception testing

1. Copy codeception yml for setup.
Copy `lando/default.codeception.yml` to `tests/codeception.yml`.
2. Add local Drush configuration for testing
    * Edit `docroot/sites/default/settings/local.settings.php` database connection to be the connection located in `docroot/sites/sparkbox_sandbox/settings/local.settings.php`.

    * Create `drush/local.drush.yml`

    ```yaml
    # # This file defines drush configuration that applies to drush commands
    # # for the entire application. For site-specific settings, like URI, use
    # # ../docroot/sites/[site]/drush.yml
    drush:
      paths:
        # Load a drush.yml configuration file from the current working directory.
        config:
          - ../docroot/sites/sparkbox_sandbox/local.drush.yml
          - docroot/sites/sparkbox_sandbox/local.drush.yml
          # Allow local global config overrides.
          - local.drush.yml
          - drush/local.drush.yml
        include:
          - '${env.home}/.drush'
          - /usr/share/drush/commands
    ```

3. Ensure your `.lando.yml` file default database is setup with `sparkbox_sandbox` as your default db.

    ```yaml
    Example:
    services:
      appserver:
        ssl: true
      database: # Override the database that comes in the drupal8
        creds:  # recipe and use it for the /sites/default site.
          user: drupal
          password: drupal
          database: sparkbox_sandbox
    ```

### To run Codeception tests locally

To run codeception tests run `lando blt codeception --group=install`. Or if you wish to run a single class/method add the annotation in the docblock `@group testme` and then run `lando blt codeception --group=testme`.

## Syncing from Staging

In order to sync from a staging or dev site, you will have to do the following:

1. In `suhumsci/docroot/sites/sparkbox_sandbox/blt.yml` or whichever relevant site you are working with, change line 10 for remote to: `remote: sparkbox_sandbox.stage` or `remote: sparkbox_sandbox.dev`.
2. Sync the database as you normally would: `lando blt drupal:sync --site=[my-multisite]`.

## Configuration for local SimpleSAML authentication

To configure the SimpleSAML module so you stop seeing the configuration errors in Drupal from that module and also to allow you to login from the /user login page with your Stanford account. (These commands should be run from the root directory.)

1. Run `lando blt sws:keys`
2. Run `lando blt sbsc`
3. Go to the the `/simplesamlphp/config` folder and edit the local.config.php file.
4. Make sure lines 10,11,12 match the information from your `lando.yml` file for the site you are working on.
    * Example: If you are working on `sparkbox_sandbox` you will want to add `sparkbox_sandbox` in for the host and the dbname on line 10 and update the username and password below to drupal.
5. After you’ve gotten that file up to date, you need to run lando blt sbsc once more and then clear your site cache with `lando drush @[site_name] cr` and the error should be gone upon reloading.

**Notes:**

* There is still some slight bugs to work out with SimplsSAML’s login but it will work for login, but after login may throw errors on the login page, this can be resolved by clearing the browser cookies for that site.

* The command for `lando drush uli` should still function with or without SimpleSAML configured to login to the local site, if this is redirecting or not functioning correctly you should ensure the module is enabled or resync your configuration on your local site.

## Common commands

* `lando drush uli` - Get a link for logging in as an admin user
* `docker ps` - Check that your docker containers are running
* `lando info` - Check your lando config
* `lando mysql -h sparkbox_sandbox` - Jump into a mysql CLI for a given multisite
* `lando drush cr` - clear cache
* `lando drush config-export` - export your local database settings
* `lando drush config-import` - import new database settings to your local.

Utilizing these commands with specific sites in your multisite setup looks like this: `lando drush @[]my-multisite] cr`.

## Troubleshooting

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `lando drush config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `lando composer install`.
* If you find yourself in a position where starting fresh is your best plan of action, `lando destroy` will completely clear your running lando instances for a clean start.
* If running `lando composer install` results in a timeout while installing a dependency, the default composer timeout for lando can be increased by running `lando composer --global config process-timeout 2000`.

## Other useful links

* [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
* [Drush configuration and aliases](../drush/README.md)
