# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

_Prerequisite: Make sure you have added your SSH key in Acquia cloud, and that it's saved in your `~/.ssh folder._

1. [Install Lando](https://lando.dev/download/).
2. Copy `/lando/default.lando.yml` to `/.lando.yml`.
3. Take the `.loc` domains in the `.lando.yml` file and add them to your `/etc/hosts` file, as shown below:
    ```
    127.0.0.1 swshumsci.suhumsci.loc
    127.0.0.1 archaeology.suhumsci.loc
    127.0.0.1 dsresaerch.suhumsci.loc
    ...
    ```
4. Build your containers: `lando rebuild`
5. Install your PHP dependencies: `lando composer install`
6. Run `lando blt blt:init:settings` and confirm that it added a `local.settings.php` file to each of your `[my-multisite]/settings` folders (ex. `/docroot/sites/default/settings/local.settings.php`).
7. Make sure the db settings in each of these `local.settings.php` files matches the settings in the `.lando.yml`. Note: the `database` service corresponds to the `default` multisite. The rest of the services have names that match their multisite.
8. Run `lando blt drupal:sync --site=default --sync-files` to pull down a copy of the database and files for the default multisite.
9. Run `lando info`, and browse to the url for your multisite.
10. Depending on the local domains you've set up, you may need to add a `docroot/sites/local.sites.php` file, and use it to add your local domains to the `$sites` array. Otherwise, requests to your local multisite domains may get sent to the default site.

# Switching between local sites
1. In your `.lando.yml` file, uncomment the service for the site you want to run locally.
2. Run `lando rebuild` (this needs to be run anytime you make changes to `.lando.yml`).
3. Confirm that the new container is running and that the password, database, and hostname values in `sites/[my-multisite]/settings/local.settings.php` correctly match the values in your `.lando.yml` file.
4. Sync the database and files with a copy from production: `lando blt drupal:sync --site=[my-multisite] --sync-files`

## Common commands
- `lando drush uli` - Get a link for logging in as an admin user
- `docker ps` - Check that your docker containers are running
- `lando info` - Check your lando config
- `lando mysql -h swshumsci_sandbox` - Jump into a mysql CLI for a given multisite

## Other useful links
- [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
- [Drush configuration and aliases](../drush/README.md)
