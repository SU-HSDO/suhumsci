# CAP Profiles Importer

The `hs_capx` module imports people and publications from the Stanford Community Academic Profiles (CAP) API into HSDP sites. Each site chooses which profiles to import by organization code or workgroup, and the imported profiles become Person and Publication content.

## How It Works

The importer talks to three CAP API endpoints:

| Endpoint | Default | Used for |
|---|---|---|
| Authentication | `https://authz.stanford.edu/oauth/token` | OAuth client credentials token for every other request |
| Organizations API | `https://api.stanford.edu` | The organization list behind the organization autocomplete on the importer form |
| Profiles API | `https://cap.stanford.edu/cap-api/api/profiles/v1` | The profile and publication data the migrations import |

Two paths make requests:

- The `capx` service (`Drupal\hs_capx\Capx`) tests credentials, syncs the organization list, and counts the profiles each importer matches so the source URLs can be split into pages. It caches its access token until shortly before the token expires.
- The `hs_capx` and `hs_capx_publications` migration groups run the imports through `stanford_migrate` on cron. They authenticate on their own through the `migrate_plus` oauth2 plugin, using the `base_uri` and `token_url` in each group's shared configuration.

`Drupal\hs_capx\Overrides\ConfigOverrides` fills in each migration group at runtime: the site's credentials, the authentication endpoint, and the source URLs built from the site's CAPx importers. None of these values are stored in the migration group configuration.

## Per-Site Configuration

| Value | Where it lives |
|---|---|
| Client ID and secret | Set on the CAPx Credentials form at `/admin/config/importers/capx/creds`. The client ID is stored in `hs_capx.settings` and the secret in a key entity. Both are excluded by `config_ignore` |
| Which profiles to import | CAPx importer entities at `/admin/config/importers/capx` |

Saving the credentials form tests the connection, clears the cached access token, syncs the organization list, and enables the profile migration.

## Overriding the API Endpoints

All three endpoints can be overridden in `settings.php` without a code deploy. Add only the settings that differ from the defaults:

```php
$settings['CAP_AUTH_URL'] = '<AUTH_URL>';
$settings['CAP_API_URL'] = '<API_URL>';
$settings['CAP_PROFILES_URL'] = '<PROFILES_URL>';

// Example:
$settings['CAP_AUTH_URL'] = 'https://auth.example.stanford.edu/oauth/token';
```

`CAP_AUTH_URL` applies to both the `capx` service and the migrations. The override splits it into the oauth2 plugin's `base_uri` and `token_url`, so no separate `$config` override is needed for the migration groups. If the value is not a valid absolute URL, an error is logged and the migrations fall back to the endpoint stored in configuration.

To change the endpoints on every site, add the settings to `keys/secrets.settings.php`, which `docroot/sites/settings/global.settings.php` includes for all sites. That file holds credentials, so confirm `keys` remains listed in `.gitignore`.

> **Important:** Migration definitions and source URLs are cached. Run a cache rebuild on each site after changing these settings, or the migrations keep using the previous endpoints.

The access token cache is keyed by the authentication URL, so changing `CAP_AUTH_URL` does not reuse a token issued by the previous server.

> **Note:** Changing the endpoints does not change the credentials. If the new endpoint requires a new client secret, update it on each site's CAPx Credentials form.
