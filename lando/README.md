# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

_Prerequisite: Make sure you have added your SSH key in Acquia cloud, and that it's saved in your `~/.ssh folder._

1. [Install Lando](https://lando.dev/download/).
2. Copy `/lando/default.lando.yml` to `/.lando.yml`.
3. Take the `.loc` domains in the `.lando.yml` file and add them to your `/etc/hosts` file, as shown below:
    ```
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
    127.0.0.1           swshumsci-sandbox.suhumsci.loc
    127.0.0.1           symsys.suhumsci.loc
    ```
4. Build your containers: `lando rebuild`
5. Install your PHP dependencies: `lando composer install`
6. Run `lando blt blt:init:settings` and confirm that it added a `local.settings.php` file to each of your `[my-multisite]/settings` folders (ex. `/docroot/sites/default/settings/local.settings.php`).
7. Make sure the db settings in each of these `local.settings.php` files matches the settings in the `.lando.yml`. Note: the `database` service corresponds to the `default` multisite. The rest of the services have names that match their multisite. For example, for the default site, make sure that these values, and key-value pairs match:
- (line 10): `$db_name = 'swshumsci';`
- 'database' => $db_name,
- 'username' => 'drupal',
- 'password' => 'drupal',
- 'host' => 'database',
8. Run `lando blt drupal:sync --site=default --sync-files --partial` to pull down a copy of the database, files for the default multisite, and the custom views and blocks that have been built out. If you **DO NOT** want to get the custom views built out on on the database you are pulling from, you can just use: `lando blt drupal:sync --site=default --sync-files`.
9. Run `lando info`, and browse to the url for your multisite. For the default site, you should be able to pull up http://swshumsci.suhumsci.loc/ in your browser.
10. Depending on the local domains you've set up, you may need to add a `docroot/sites/local.sites.php` file, and use it to add your local domains to the `$sites` array. Otherwise, requests to your local multisite domains may get sent to the default site.

# Switching between local sites
1. In your `.lando.yml` file, uncomment the service for the site you want to run locally.
2. Run `lando rebuild` (this needs to be run anytime you make changes to `.lando.yml`).
3. Confirm that the password, database, and hostname values in `sites/[my-multisite]/settings/local.settings.php` correctly match the values in your `.lando.yml` file.
3. Create a new file in `/docroot/sites` called `local.sites.php`. This adds new routing based on our local environments and local `sites/` folders. Add the following code there:
```
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
4. Sync the database and files with a copy from production: `lando blt drupal:sync --site=[my-multisite] --sync-files`.
5. From now on, when you run a cache clear or try to get an admin link, you'll need to specify which Drupal site you are performing the action, for instance, for the default site: `lando drush @default.local cr` and for the economics site: `lando drush @economics.local uli`.

## Common commands
- `lando drush uli` - Get a link for logging in as an admin user
- `docker ps` - Check that your docker containers are running
- `lando info` - Check your lando config
- `lando mysql -h swshumsci_sandbox` - Jump into a mysql CLI for a given multisite
- `lando drush cr` - clear cache
- `lando drush config-export` - export your local database settings
- `lando drush config-import` - import new database settings to your local.

## Other useful links
- [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
- [Drush configuration and aliases](../drush/README.md)
