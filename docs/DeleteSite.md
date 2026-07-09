# H&S Site Decommission and Deletion Guide

This document outlines the workflow for decommissioning and deleting a site in the HSDP application. It covers both decommissioning (removing a site from service but retaining data) and full deletion (removing all traces of a site from the platform and Acquia). Follow these steps to ensure a clean and secure process.


## Overview

This guide describes the process for backing up, decommissioning, and deleting a site. It covers:
- Backing up a site's database and files
- Decommissioning a site (removing it from service, but retaining data)
- Deleting a site (removing all traces from the platform and Acquia)

Follow these steps to ensure a clean and secure process.


## Decommission vs. Deletion

### Decommission a Site (Remove from Service)

- **Technical steps:** Remove NetDB pointers, remove domains, remove from multi-site, remove from WAF/CDN. Keep database and `docroot/sites/<SITE>` (site files).
- **Effects:** Site is inaccessible and no commands are run (no updates, cron, scripts, etc.). Database and files remain on Acquia and in the codebase.
- **Pros/cons:** Easy to restore; continues to take up storage and may appear in codebase/UI.

### Delete a Site (Remove in Totality)

- **Technical steps:** Back up the database and files, decommission the site, then delete the database and the `docroot/sites/<SITE>` folder.
- **Effects:** Site is completely removed from codebase and Acquia.
- **Pros/cons:** Frees up storage and cleans up codebase/UI; cannot restore unless backups are kept.

> **Note:** Decommissioning without also deleting is rare. It's mainly useful when it isn't yet clear whether a site should be kept, and you want to take it offline while preserving the option to restore it. Most of the time, decommissioning and deletion happen together. When that's the case, perform the codebase changes for both in a single branch and pull request; see the note in [Remove Site Configuration from Codebase](#remove-site-configuration-from-codebase).

> **Recommendation:** Start with decommissioning, then delete later. For sensitive or security-related removals, delete immediately and store backups securely.

Clarify with H&S whether the site should be decommissioned or deleted.


## Requirements

See [Development Requirements](DevelopmentRequirements.md)


## Back Up the Site

> **Important:** Take these backups before decommissioning. Decommissioning removes the domains and Drush alias that `pull:database` and `drush rsync` depend on, so back up first while access is still live.

### Back Up the Database

Create and download the latest backup, either via ACLI or the Acquia Cloud UI.

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
mv <FILEPATH> ~/site-backups/<SITE>-prod-db-<YYYY-MM-DD>.sql.gz
```

**With the Acquia Cloud UI** (use this if ACLI isn't installed or configured):

1. Open the Acquia Cloud UI for the HumSci Gryphon application.
1. Click on the production environment (Prod).
1. Click on the Databases tab in the left-side navigation.
1. Find the database for the site, click its three-dot button, then **Backup**.
1. Once the backup completes, click the three-dot button again and **Download** it.
1. Rename the downloaded backup to a clear, dated format such as `<SITE>-prod-db-<YYYY-MM-DD>.sql.gz`.

> **Important:** Whichever method you used above, upload the renamed backup to a secure internal storage location as directed by your team before continuing. Refer to internal documentation for the correct location.

### Back Up Site Files

Replace `<SITE>` with the alias of the site you are backing up.

#### Create a Local Directory

```bash
mkdir -p ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>
```

#### Download Files via rsync

```bash
drush rsync @<SITE>.prod:%files/ ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>/files
drush rsync @<SITE>.prod:%private/ ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>/files-private
```

#### Archive the Backup

```bash
tar -czvf ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>.tar.gz ~/site-backups/<SITE>-prod-files-<YYYY-MM-DD>
```

#### Upload and Clean Up

Upload the file backup to a secure internal storage location as directed by your team. Refer to internal documentation for the correct location.

Delete local backups after uploading.


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

> **Note:** If deleting the site in full rather than only decommissioning, also remove the site directory (see [Remove the Site from the Codebase](#remove-the-site-from-the-codebase)) in this same branch, so both sets of changes ship in a single pull request.

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

> **Important:** Backups should already exist from the [Back Up the Site](#back-up-the-site) steps performed at decommissioning time, capturing the site's state before it became inaccessible. No new backup is required before deletion. If unsure whether a backup exists, check the secure storage location for one before proceeding.


### Remove the Site from the Codebase

If you already removed the site directory in the same branch as the [decommissioning codebase changes](#remove-site-configuration-from-codebase), skip this step.

Otherwise, create a new branch and remove the site directory:

```bash
rm -rf docroot/sites/<SITE>
```

Create a new PR for these changes.


### Delete the Database from Acquia

**With the Acquia Cloud UI:**

1. Open the Acquia Cloud UI for the HumSci Gryphon application.
1. Click on the production environment (Prod).
1. Click on the Databases tab in the left-side navigation.
1. Find the database for the site, click its three-dot button, then **Delete**.

**With ACLI:**

```bash
# Using an application ID:
acli api:applications:database-delete <APP_ID> <SITE>

# Using the humscigryphon alias (the alias rarely changes and is easier to remember):
acli api:applications:database-delete humscigryphon <SITE>
```

---

## Best Practices & Final Notes

- Always confirm with H&S whether a site should be decommissioned or deleted.
- Back up both the database and files before decommissioning. Decommissioning removes the access that backups depend on.
- Communicate with the H&S web team before and after site removal.
- Document any manual steps or exceptions for future reference.
- For sensitive or security-related removals, prioritize deletion and secure backup storage.

> **Note:** If you encounter issues or have questions, reach out to the H&S development team for support.
