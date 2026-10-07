# CAP Profiles Importer

The `hs_capx` module imports people and publications from the Stanford Community Academic Profiles (CAP) API into HSDP sites. Each site chooses which profiles to import by organization code or workgroup.

## How It Works

- The imports are Drupal migrations defined in configuration, using `migrate_plus` and run on cron by `stanford_migrate`. See the `migrate_plus.migration.hs_capx*` and `migrate_plus.migration_group.hs_capx*` files in `config/default`.
- The site's credentials and source URLs are added to the migration groups at runtime by [`ConfigOverrides.php`](../docroot/modules/humsci/hs_capx/src/Overrides/ConfigOverrides.php). They are not stored in configuration.
- [`Capx.php`](../docroot/modules/humsci/hs_capx/src/Capx.php) handles everything outside the import itself: testing credentials and syncing the organization list used by the importer form.

## Per-Site Configuration

| Value | Where |
|---|---|
| Client ID and secret | `/admin/config/importers/capx/creds` |
| Which profiles to import | `/admin/config/importers/capx` |

## CAP API Endpoints

| Endpoint | Setting | Default |
|---|---|---|
| Authentication | `CAP_AUTH_URL` | `https://authz.stanford.edu/oauth/token` |
| Organizations API | `CAP_API_URL` | `https://api.stanford.edu` |
| Profiles API | `CAP_PROFILES_URL` | `https://cap.stanford.edu/cap-api/api/profiles/v1` |

The defaults are in `Capx.php`. Each can be overridden in `settings.php` so the H&S web team can respond to a CAP API endpoint change without a code deploy:

```php
$settings['CAP_AUTH_URL'] = '<AUTH_URL>';

// Example:
$settings['CAP_AUTH_URL'] = 'https://auth.example.stanford.edu/oauth/token';
```

`CAP_AUTH_URL` also sets the authentication endpoint for the migrations, so no separate `$config` override is needed.

### Override the Endpoints on an Acquia Environment

1. SSH into the environment and edit `/mnt/gfs/humscigryphon.<ENV>/secrets.settings.php`, where `<ENV>` is `dev`, `test` (staging), or `prod`. The file applies to every site on that environment.

1. Check the file for syntax errors with `php -l`.

1. Rebuild the cache on every site that uses the importer. The migrations keep using the previous endpoints until the cache is rebuilt.

1. Repeat on each environment that needs the change.

> **Warning:** A syntax error in the secrets file takes down every site on the environment. Take a copy of the file before editing it.

> **Note:** Changing the endpoints does not change the credentials. If the new endpoint requires a new client secret, update it on each site's credentials form.

To test locally, add the settings to `keys/secrets.settings.php`. Running `drush sws:keys` overwrites that file with a fresh copy from production.
