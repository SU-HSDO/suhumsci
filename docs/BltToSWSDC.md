# 🚀 Migrating Your Local Environment from BLT to SWS Drush Commands (SWSDC)

This guide explains how to update your local development environment to use SWS Drush Commands (SWSDC) instead of Acquia BLT. It covers the essential steps and provides a quick command parity table for reference. Most developers will only need a handful of new commands for daily work.

- ℹ️ **Note:** Lando and DDEV configuration files have been updated for SWSDC, but these setups have not been fully tested. Additional changes may be required for local development in either container environment.

---

## Why migrate? 🤔
- BLT is deprecated and not compatible with Drupal 11 and beyond.
- SWS Drush Commands (SWSDC) is the supported replacement for Stanford Drupal projects.

---

## Migration Steps (Local Developer Checklist)

1. **Pull the latest code from the main branch after the migration PR is merged.** 🛠️
2. **Install dependencies:**
   - Run `composer install` to ensure SWSDC is present and BLT is removed.
3. **Update your Drush local config:**
   - Run `drush sws:multisite:settings` to scaffold a starting config.
   - Create or edit `drush/local.drush.yml` (see example below).
   - Copy your DB credentials from `blt/local.blt.yml` if needed.
   - Get your Acquia API key/secret from `cat ~/.acquia/cloud_api.conf` or create a new key in your Acquia account.
4. **Remove any local BLT-specific configuration:**
   - Delete or archive your `blt/local.blt.yml` and any custom BLT scripts once your local.drush.yml is working.
5. **Update your local settings files:**
   - For your local environment, update each `docroot/sites/<site>/settings/local.settings.php` as needed.
   - **Most important:** Replace any usage of the old BLT environment detector with the new SWS Drush version in all local settings files:
     - Replace:
       ```php
       use Acquia\Blt\Robo\Common\EnvironmentDetector;
       ```
       with:
       ```php
       use Drupal\SwsDrush\Helpers\EnvironmentDetector;
       ```
     - You can do this in all PHP files under `docroot/sites` with:
       ```sh
       find docroot/sites -type f -name "*.php" -exec sed -i 's|use Acquia\\Blt\\Robo\\Common\\EnvironmentDetector;|use Drupal\\SwsDrush\\Helpers\\EnvironmentDetector;|g' {} +
       ```
   - Review any other customizations in your local settings and update as needed for SWSDC compatibility.
6. **Review and update scripts or aliases:**
   - Update any local scripts or aliases that referenced `blt` to use the new SWSDC commands.
7. **Test your environment:** ✅
   - Run the common SWSDC commands below to verify your setup.

---

## Example `drush/local.drush.yml`
```yaml
command:
  sws:
    options:
      db-port: '3306'
      db-host: localhost
      db-user: <your-db-user>
      db-pass: <your-db-pass>
      db-name: <your-db-name>
      app-key: <acquia-key>
      app-secret: <acquia-secret>
```
*Replace the values above with your actual local database credentials and Acquia API key/secret.*

---

## Command Parity Table
There is basic command parity from BLT to SWSDC. Only commands frequently used by developers are included in the table below. Additionally, if a command can be easily replaced with use of ACLI (Acquia CLI) or regular drush commands, that is preferred over a custom command. 🗝️

| BLT Command                        | SWSDC Command Equivalent                      | Notes                                  |
|-------------------------------------|----------------------------------------------|----------------------------------------|
| BLT Command                        | SWSDC Command Equivalent                      | Notes                                  |
|-------------------------------------|----------------------------------------------|----------------------------------------|
| `blt drupal:install`                | `drush sws:multisite:install -n`             | Site install                           |
| `blt deploy`                        | `drush sws:artifact:deploy -v -n`            | Artifact deployment                    |
| `blt drupal:sync`                   | `drush sws:site:sync`                        | Site/db sync                           |
| `blt drupal:sync:public-files`      | `drush sws:site:syncFiles`                   | Sync public files                      |
| `blt drupal:sync:private-files`     | `drush sws:site:syncFiles`                   | Sync private files                     |
| `blt tests` / `blt tests:phpunit:run` | `drush sws:source:tests:phpunit`            | Run PHPUnit tests                      |
| `blt tests:codeception:run`         | `drush sws:codeception`                      | Run Codeception tests                  |
| `blt sws:keys`                          | `drush sws:keys`                             | Sync key secret files                  |
| `blt blt:init:settings`                 | `drush sws:multisite:settings`               | Scaffold multisite settings files      |
| `blt recipes:multisite:init`        | `drush sws:multisite:new-site`               | Provision new multisite                |
| (custom BLT commands)`              | (see PR notes / custom Drush commands)       | Some custom commands may be ported     |

---

## Troubleshooting
- 🔒 **Security Reminder:** Never commit your `local.drush.yml` or any file containing secrets or credentials to the repository.
- ℹ️ **Note:** Lando and DDEV configuration files have been updated for SWSDC, but these setups have not been fully tested. Additional changes may be required for local development in either container environment.
- ⚠️ If you see errors about missing BLT, ensure you have removed all BLT config and run `composer install`.
- 📝 If you have custom local settings, merge them carefully with the new SWSDC includes.