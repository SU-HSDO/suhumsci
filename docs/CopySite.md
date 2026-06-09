# Copy a Site

Every now and then an existing site will need to be copied to a new alias. This is primarily a maintenance task to clean up and organize sites on the stack.

This guide assumes the site being copied is from a non-matching URL-alias combination to a matching URL-alias. A few adjustments may need to be made if not.

Example scenarios:

1. A new site is created to replace an existing site. For example, `mathematics` had a `mathematics2024` site created, and eventually `mathematics2024` was launched at `mathematics.stanford.edu` and the original `mathematics` site was no longer used. As part of maintenance and clean-up (and to avoid confusion), the `mathematics2024` site would be copied over the `mathematics` site, then `mathematics2024` would be removed.
1. A site is provisioned before the final domain or name is known. Once the final domain is known, provision a new site to match the final domain and copy the original provision to it to align the domain with the site name.
1. Instead of starting from a blank slate, a new site wants to start with content, configuration, etc., from an existing site.

## Requirements

See [Development Requirements](DevelopmentRequirements.md).

## Provision a New Site to Copy To

If the site to be copied to does not exist, the first step is to [provision a new site](NewSite.md) to be copied to. Follow all the steps up to the deployment of the code. The profile install is unnecessary because this is not for a fresh site — the existing site will be copied over.

The provision step needs to happen first and the copy cannot continue until the provisioned code is deployed.

## Identify SOURCE and DESTINATION

### Identify the Aliases

Before continuing, be certain of the site alias you are copying from and the alias you are copying to. This guide refers to them as `<SOURCE>` (the site being copied) and `<DESTINATION>` (the site being copied to).

In the example above, `mathematics2024` is `<SOURCE>` and `mathematics` is `<DESTINATION>`.

### Verify DESTINATION URLs Were Provisioned

Make sure the following aliases have been set up correctly in [NetDB](https://netdb.stanford.edu/) — especially the `-prod` URL — before continuing.

- `<DESTINATION>-dev`
- `<DESTINATION>-test` (the staging environment)
- `<DESTINATION>-prod`

If the `-prod` alias is on the WAF (the `edgesuite.net` NetDB node), verify the alias is pointed correctly in the [WAF configuration](https://control.akamai.com/apps/home-page/#/home).

If the provision was not done correctly or the URLs are not set up, correct those before continuing.

## Copy the Site

Before starting, notify the H&S web team and/or site owner that there will be brief downtime and they should refrain from editing the site until the copy is complete. Any edits made to the site after the database is copied down locally will not be transferred.

Check the `<DESTINATION>` site's `/admin/content` page to verify there are no recent edits that would be overwritten, and `/admin/users` to verify there are no active editors.

### Create Database Backups

If `<SOURCE>` is going to be shut down or re-purposed, create a backup in case it needs to be restored or referenced.

Create a backup of `<DESTINATION>` as well, in case there is confusion during the process.

> **Warning:** Complete both backups before proceeding. The next steps will drop and overwrite the `<DESTINATION>` database. Confirm backups exist and are readable before continuing.

### Copy the Source Database and Files Locally

Create a working directory in your local environment. Using a consistent parent directory is recommended.

```bash
mkdir -p ~/site-copies/<SOURCE>-<DESTINATION>-transfer
```

Copy the `<SOURCE>` database:

```bash
drush -Dssh.tty=0 @<SOURCE>.prod sql-dump > ~/site-copies/<SOURCE>-<DESTINATION>-transfer/<SOURCE>.sql
```

Create directories for files and private files:

```bash
mkdir ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files
mkdir ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files-private
```

Copy the `<SOURCE>` files. Private files typically only exist on intranet sites, but copy them regardless.

```bash
drush rsync @<SOURCE>.prod:%files/ ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files
drush rsync @<SOURCE>.prod:%private/ ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files-private
```

Verify the files exist:

```bash
ls -ll ~/site-copies/<SOURCE>-<DESTINATION>-transfer
ls -ll ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files
ls -ll ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files-private
```

### Push the Source Database and Files to the Destination

> **Important:** Double-check that `<SOURCE>` and `<DESTINATION>` are correct before continuing.

> **Warning:** The following command drops the entire `<DESTINATION>` database. Confirm backups are in place before running it.

Drop the existing `<DESTINATION>` database:

```bash
drush @<DESTINATION>.prod sql-drop -y
```

Push the `<SOURCE>` database to `<DESTINATION>`:

```bash
drush @<DESTINATION>.prod sql-cli < ~/site-copies/<SOURCE>-<DESTINATION>-transfer/<SOURCE>.sql
```

Push the `<SOURCE>` files and private files to `<DESTINATION>`:

```bash
drush rsync ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files/ @<DESTINATION>.prod:%files/
drush rsync ~/site-copies/<SOURCE>-<DESTINATION>-transfer/files-private/ @<DESTINATION>.prod:%private/
```

Unset the canonical URL and URL redirect on both sites:

```bash
drush @<DESTINATION>.prod cdel domain_301_redirect.settings domain
drush @<SOURCE>.prod cdel domain_301_redirect.settings domain
```

Rebuild the cache on `<DESTINATION>`:

```bash
drush @<DESTINATION>.prod cr
```

Clear Varnish via the Acquia UI or ACLI:

```bash
acli api:environments:domain-clear-caches <APP_UUID> <DESTINATION>.stanford.edu
acli api:environments:domain-clear-caches <APP_UUID> <DESTINATION>-prod.stanford.edu
acli api:environments:domain-clear-caches <APP_UUID> <SOURCE>-prod.stanford.edu
```

> **Note:** You may also need to clear the Akamai CDN cache if the site does not update.

Review `<DESTINATION>` and compare it to `<SOURCE>`:

```
https://<DESTINATION>-prod.stanford.edu
https://<SOURCE>-prod.stanford.edu
```

Verify both sites load from the correct alias. On each site, open the Status Report page at `/admin/reports/status` (or **Reports > Status Report**) and check the Stanford Site Alias line.

> **Important:** Confirm the database and files were pushed successfully before removing local copies.

Remove the local transfer files:

```bash
rm -rf ~/site-copies/<SOURCE>-<DESTINATION>-transfer
```

The site copy is now complete. If the site is live, proceed to update domain pointing.

## Update Live Domain Pointing

This section is only required if `<DESTINATION>` is replacing the live `<SOURCE>` site.

### Point the Live Domain from SOURCE to DESTINATION

Using the workaround file on the server, point the live `<SOURCE>` URL to `<DESTINATION>`. This guide refers to that URL as `<LIVEURL>` (e.g., `mathematics.stanford.edu`).

> **Note:** It may appear redundant to point a matching URL to `<DESTINATION>` (for example, `MYSITE.stanford.edu` to `MYSITE`) because of the auto-pointing logic in `sites.php`. However, if the URL does not currently match the alias, it is hard-coded in `sites.php` and must be overridden. This is usually the issue a copy request is meant to correct.

SSH into the server:

```bash
drush @<DESTINATION>.prod ssh
```

Navigate to the workaround directory:

```bash
cd /mnt/files/humscigryphon.prod
```

> **Warning:** The following step edits a live server configuration file. Changes take effect immediately for all traffic. Edit carefully.

Open `sites.php` in a text editor:

```bash
nano sites.php
```

Check for an existing entry pointing the live URL to `<SOURCE>` (e.g., `$sites['<LIVEURL>.stanford.edu'] = '<SOURCE>';`).

- If a pointer exists and the live URL matches `<DESTINATION>`: remove the entry entirely. The `sites.php` auto-pointing logic handles matching URL-to-alias resolution.
- If a pointer exists and the live URL does not match `<DESTINATION>`: update the entry to point to `<DESTINATION>`:
  ```php
  $sites['<LIVEURL>.stanford.edu'] = '<DESTINATION>';
  ```
- If no pointer exists: add one:
  ```php
  $sites['<LIVEURL>.stanford.edu'] = '<DESTINATION>';
  ```

Save the file, then rebuild caches and clear Varnish:

```bash
drush @<DESTINATION>.prod cr
```

```bash
acli api:environments:domain-clear-caches <APP_UUID> <LIVEURL>.stanford.edu
acli api:environments:domain-clear-caches <APP_UUID> <DESTINATION>-prod.stanford.edu
```

Open the live URL and verify the site loads from `<DESTINATION>`. Check the Status Report page at `/admin/reports/status` and confirm the Stanford Site Alias line shows `<DESTINATION>`, not `<SOURCE>`.

Set the canonical URL redirect:

```bash
drush @<DESTINATION>.prod cset domain_301_redirect.settings domain https://<LIVEURL>.stanford.edu/
```

Rebuild the cache after setting the redirect:

```bash
drush @<DESTINATION>.prod cr
```

Verify the canonical redirect is working by opening `https://<DESTINATION>-prod.stanford.edu/?foo` in an incognito window and confirming it redirects to `https://<LIVEURL>.stanford.edu/?foo`.

Rebuild the cache:

```bash
drush @<DESTINATION>.prod cr
```

### Update Live Domain Pointing in sites.php

1. Create a new branch in the `suhumsci` repo.
1. Edit `docroot/sites/sites.php`.
1. Update the URL entries:
   - If the live URL matches `<DESTINATION>`: remove any pointer of the live URL to `<SOURCE>` and let `sites.php` auto-point the matching URL to the matching alias.
   - If the live URL does not match `<DESTINATION>`: update the pointer to `<DESTINATION>`:
     ```php
     $sites['<LIVEURL>.stanford.edu'] = '<DESTINATION>';
     ```
1. Create a PR and assign it.

## Completion and Next Steps

The copy is complete. Notify the H&S web team and/or site owner that they can resume editing.

### Copy Databases Down

Copy the `<DESTINATION>` database from production to staging and dev using the [Acquia Cloud UI](https://cloud.acquia.com/a/applications):

1. Open the HumSci Gryphon application.
1. Drag the Databases tab from Prod to Stage.
1. Select only the `<DESTINATION>` database.
1. Click "Continue" and then click "Copy".
1. Repeat from Prod to Dev.

### SOURCE Site Next Steps

- If `<SOURCE>` is being re-used for a fresh install with a matching URL, complete the [provision install steps](NewSite.md) on `<SOURCE>`.
- If `<SOURCE>` is no longer going to be used, it can be decommissioned at a later date. The H&S web team and/or site owner may want `<SOURCE>` to remain available at `<SOURCE>-prod.stanford.edu` for a period of time.
