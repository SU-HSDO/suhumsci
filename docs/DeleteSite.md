# H&S Site Decommission and Deletion Guide

This document outlines the workflow for decommissioning and deleting a site in the H&S application. It covers both decommissioning (removing a site from service but retaining data) and full deletion (removing all traces of a site from the platform and Acquia). Follow these steps to ensure a clean and secure process.


## Overview

This guide describes the process for decommissioning and deleting a site. It covers:
- Decommissioning a site (removing it from service, but retaining data)
- Deleting a site (removing all traces from the platform and Acquia)

Follow these steps to ensure a clean and secure process.


## Decommission vs. Deletion

### Decommission a site (remove from service)

- **Technical steps:** Remove NetDB pointers, remove domains, remove from multi-site, remove from WAF/CDN. Keep database and `docroot/sites/<site>` (site files).
- **Effects:** Site is inaccessible and no commands are run (no updates, cron, scripts, etc.). Database and files remain on Acquia and in the codebase.
- **Pros/cons:** Easy to restore; continues to take up storage and may appear in codebase/UI.

### Delete a site (remove in totality)

- **Technical steps:** Decommission the site first, then delete the database and `docroot/sites/<site>` folder.
- **Effects:** Site is completely removed from codebase and Acquia.
- **Pros/cons:** Frees up storage and cleans up codebase/UI; cannot restore unless backups are kept.

> **Recommendation:** Start with decommissioning, then delete later. For sensitive or security-related removals, delete immediately and store backups securely.

Clarify with H&S whether the site should be decommissioned or deleted.


## Requirements

See [Development Requirements](DevelopmentRequirements.md)


## Decommissioning Steps

### Remove Site Configuration from Codebase

Create a new branch.

Update Drush multi-sites array:
- Edit `drush/drush.yml` and remove the site alias from the multisite array.

Remove the site Drush alias YAML:

```bash
git rm drush/sites/<SITENAME>.site.yml
```

Remove site alias from Lando and DDEV configuration:
- Edit `.ddev/default.config.yaml` (or `.ddev/config.yaml`)
- Edit `lando/default.lando.yml`

Remove any custom domain forwarding from `docroot/sites/sites.php` (if present).

Create a new PR for these changes.


### Remove Domains from NetDB

Remove `-dev`, `-stage`, `-prod`, and live domains from NetDB:
- Check both the [humscigryphon](https://netdb.stanford.edu/node_info?name=humscigryphon.stanford.edu) and [Akamai WAF/CDN](https://netdb.stanford.edu/node_info?name=stanfordedu.edgesuite.net) NetDB nodes.
- The live domain may differ from the site alias and other domains.


### Remove Domains from Acquia Environments

Remove `-dev`, `-stage`, `-prod`, and live domains from Acquia environments:
1. Open the Acquia Cloud UI for the HumSci Gryphon application.
1. Click on the production environment (Prod).
1. Click on the Domain Management tab in the left-side navigation.
1. Find the relevant domains for the site.
1. Click the three-dot button for the relevant domains and delete them.
1. Repeat for staging (Stage) and development (Dev) environments.

Alternatively, use ACLI if installed. You can target each environment by ID or by its `humscigryphon.<env>` application alias (the alias rarely changes and is easier to remember):

```bash
# Using environment IDs:
acli api:environments:domain-delete <PROD_ENV_ID> <SITE>-prod.stanford.edu
acli api:environments:domain-delete <STAGE_ENV_ID> <SITE>-stage.stanford.edu
acli api:environments:domain-delete <DEV_ENV_ID> <SITE>-dev.stanford.edu

# Using aliases:
acli api:environments:domain-delete humscigryphon.prod <SITE>-prod.stanford.edu
acli api:environments:domain-delete humscigryphon.test <SITE>-stage.stanford.edu
acli api:environments:domain-delete humscigryphon.dev <SITE>-dev.stanford.edu
```


### Remove Domains from Akamai WAF/CDN

Remove relevant `-dev`, `-stage`, `-prod`, and live domains from the Akamai WAF/CDN. Alternatively, make a note that these domains can be removed during a later Akamai configuration update done in bulk.


## Deletion Steps

> **Important:** Perform all decommissioning steps first, then follow these steps to delete the site entirely.


### Remove the Site from the Codebase

Create a new branch.

Remove the site directory:

```bash
rm -rf docroot/sites/<SITE>
```

Create a new PR for these changes.

### Remove the Database from Acquia

Before deleting the database, create and download the latest backup, either via ACLI or the Acquia Cloud UI.

**With ACLI:**

Create a backup, then download it without importing it anywhere. You can target the environment by ID or by its `humscigryphon.prod` application alias (the alias rarely changes and is easier to remember):

```bash
# Using an environment ID:
acli api:environments:database-backup-create <PROD_ENV_ID> <SITE>
acli pull:database --no-import <PROD_ENV_ID> <SITE>

# Using the humscigryphon.prod alias:
acli api:environments:database-backup-create humscigryphon.prod <SITE>
acli pull:database --no-import humscigryphon.prod <SITE>
```

The command prints the downloaded file's path in the system temp directory when it finishes. Move it to your backup location and rename it to a clear, dated format:

```bash
mkdir -p ~/site-backups
mv /tmp/prod-<SITE>-db<ID>-<TIMESTAMP>.sql.gz ~/site-backups/<SITE>-prod-db-<YYYY-MM-DD>.sql.gz
```

**With the Acquia Cloud UI** (use this if ACLI isn't installed or configured):

1. Open the Acquia Cloud UI for the HumSci Gryphon application.
1. Click on the production environment (Prod).
1. Click on the Databases tab in the left-side navigation.
1. Find the database for the site, click its three-dot button, then **Backup**.
1. Once the backup completes, click the three-dot button again and **Download** it.
1. Rename the downloaded backup to a clear, dated format such as `<SITE>-prod-db-<YYYY-MM-DD>.sql.gz`.

> **Important:** Whichever method you used above, upload the renamed backup to a secure internal storage location as directed by your team before continuing. Refer to internal documentation for the correct location.

Once the backup is confirmed, delete the database:

1. Open the Acquia Cloud UI for the HumSci Gryphon application.
1. Click on the production environment (Prod).
1. Click on the Databases tab in the left-side navigation.
1. Find the database for the site, click its three-dot button, then **Delete**.

### Backup Site Files

Replace `<SITE>` with the alias of the site you are backing up.

#### Create a Local Directory

```bash
mkdir -p ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>
```

#### Download Files via rsync

If the site has already been decommissioned and the current branch no longer has the site Drush alias, check out an older commit or branch where the alias still exists, then run `drush rsync` from there.

**With drush rsync from an older checkout if needed:**

```bash
git checkout <commit-or-branch-with-site-alias>
drush rsync @<SITE>.prod:%files/ ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>/files
drush rsync @<SITE>.prod:%private/ ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>/files-private
```

Return to your working branch after the backup is complete.


#### Archive the Backup

```bash
tar -czvf ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>.tar.gz ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>
```

#### Upload and Clean Up

Upload the file backup to a secure internal storage location as directed by your team. Refer to internal documentation for the correct location.

Delete local backups after uploading.

---

## Best Practices & Final Notes

- Always confirm with H&S whether a site should be decommissioned or deleted.
- Keep backups of both database and files before deletion.
- Communicate with stakeholders before and after site removal.
- Document any manual steps or exceptions for future reference.
- For sensitive or security-related removals, prioritize deletion and secure backup storage.

> **Note:** If you encounter issues or have questions, reach out to the H&S development team for support.
