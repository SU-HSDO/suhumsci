# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

1. Copy `/lando/default.lando.yml` to `/.lando.yml`. Uncomment the multisite that you want to run locally.
2. Take the `.loc` domains in the `.lando.yml` file and add them to your `/etc/hosts` file.
3. [Install Lando](https://lando.dev/download/).
4. Build your containers: `lando rebuild`
5. Install your PHP dependencies: `lando composer install`
6. Check that you have `local.settings.php` files in your multisite folders (ex. `/docroot/sites/default/settings/local.settings.php`).
  - If not, maybe run `lando blt blt:init:settings`), and make sure the db settings match mine and/or the `.lando.yml` file.
7. Run `lando blt drupal:sync --site=default --sync-files` to sync the default multisite (db and files).
	- You'll need your SSH key to be added in Acquia Cloud to sync files via `blt`.
	- You may need to run this if you need to sync files: `lando blt drupal:sync:files`
8. Run `lando info`, and browse to the url for your multisite.

## Common commands
- `docker ps` - Check that your docker containers are running
- `lando info` - Check your lando config
- `lando mysql -h swshumsci_sandbox` - Jump into a mysql CLI for a given multisite
- `lando drush uli` - Get a link for logging in as an admin user

## Other useful links
- [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
