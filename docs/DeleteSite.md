# 🗑️ H&S Site Decommission and Deletion Guide

This document outlines the workflow for decommissioning and deleting a site in the H&S application. It covers both decommissioning (removing a site from service but retaining data) and full deletion (removing all traces of a site from the platform and Acquia). Follow these steps to ensure a clean and secure process.


## 📝 Overview

This guide describes the process for decommissioning and deleting a site. It covers:
- Decommissioning a site (removing it from service, but retaining data)
- Deleting a site (removing all traces from the platform and Acquia)

Follow these steps to ensure a clean and secure process.


## 🔄 Decommission vs. Deletion

### Decommission a site (remove from service)

- **Technical steps:** Remove NetDB pointers, remove domains, remove from multi-site, remove from WAF/CDN. Keep database and `docroot/sites/<site>` (site files).
- **Effects:** Site is inaccessible and no commands are run (no updates, cron, scripts, etc.). Database and files remain on Acquia and in the codebase.
- **Pros/cons:** Easy to restore; continues to take up storage and may appear in codebase/UI.

### Delete a site (remove in totality)

- **Technical steps:** Decommission the site first, then delete the database and `docroot/sites/<site>` folder.
- **Effects:** Site is completely removed from codebase and Acquia.
- **Pros/cons:** Frees up storage and cleans up codebase/UI; cannot restore unless backups are kept.

> ⚠️ **Recommendation:** Start with decommissioning, then delete later. For sensitive/security-related removals, delete immediately and store backups securely.

Clarify with H&S whether the site should be decommissioned or deleted.


## 📋 Requirements

See [Development Requirements](DevelopmentRequirements.md)


## Decommissioning Steps

### Remove Site Configuration from Codebase

🚩 Create a new branch.

Update Drush multi-sites array:
- Edit `drush/drush.yml` and remove the site alias from the multisite array.

Remove the site Drush alias YAML:

```bash
git rm drush/sites/SITENAME.site.yml
```

Remove site alias from Lando and DDEV configuration:
- Edit `.ddev/default.config.yaml` (or `.ddev/config.yaml`)
- Edit `lando/default.lando.yml`

Remove any custom domain forwarding from `docroot/sites/sites.php` (if present).

✅ Create a new PR for these changes.


### Remove Domains from NetDB

Remove `-dev`, `-stage`, `-prod`, and live domains from NetDB:
- Check both the [humscigryphon](https://netdb.stanford.edu/node_info?name=humscigryphon.stanford.edu) and [WAF](https://netdb.stanford.edu/node_info?name=stanfordedu.edgesuite.net) NetDB nodes.
- The live domain may differ from the site alias and other domains.


### Remove Domains from Acquia Environments

Remove `-dev`, `-stage`, `-prod`, and live domains from Acquia environments:
1. Open the Acquia Cloud UI for the HumSci Gryphon application.
2. Click on the production environment (Prod).
3. Click on the Domain Management tab in the left-side navigation.
4. Find the relevant domains for the site.
5. Click the three-dot button for the relevant domains and delete them.
6. Repeat for staging (Stage) and development (Dev) environments.

💻 Alternatively, use ACLI if installed:

```bash
# HumSci Gryphon APP ID
export APP_ID=60ee2ebb-94f3-415d-a289-c23889ecec18
# List environment IDs
acli api:applications:environment-list $APP_ID
# Remove domains
acli api:environments:domain-delete ENV_ID SITE-prod.stanford.edu
acli api:environments:domain-delete ENV_ID SITE-stage.stanford.edu
acli api:environments:domain-delete ENV_ID SITE-dev.stanford.edu
```


### 4️⃣ Remove Domains from WAF

Remove `-dev`, `-stage`, `-prod`, and live domains from the WAF. Alternatively, make a note that these domains can be removed from the WAF and do it a later time in bulk with other WAF configuration changes (recommended).


## 🗑️ Deletion Steps

> 🚨 **Important:** Perform all decommissioning steps first, then follow these steps to delete the site entirely.


### Remove the Site from the Codebase

Create a new branch.

Remove the site directory:

```bash
rm -rf docroot/sites/SITE
```

✅ Create a new PR for these changes.

### Remove the Database from Acquia

1. Open the Acquia Cloud UI for the HumSci Gryphon application.
2. Click on the production environment (Prod).
3. Click on the Databases tab in the left-side navigation.
4. Find the database for the site.
5. **Download the latest database backup first.**
   - Upload it to a secure internal storage location as directed by your team. Refer to internal documentation for the correct URL.
6. Delete the database.

### Backup Site Files

Replace `SITE` with the alias of the site you are backing up.

#### 📁 Create a Local Directory

```bash
mkdir ~/site-backups/SITE-files-backup_YYYY-MM-DD
```

#### 🔄 Download Files via rsync

**With drush rsync (requires active URLs):**

This likely won't work since the URL's have been decommissioned (use vanilla steps below).

```bash
drush rsync @SITE.prod:%files/ ~/site-backups/SITE-files-backup_YYYY-MM-DD/files
drush rsync @SITE.prod:%private/ ~/site-backups/SITE-files-backup_YYYY-MM-DD/files-private
```

**With vanilla rsync:**

Replace the server URL with the correct URL/host for the production server. This should be available in the codebase in Drush configuration or can be retrieved through ACLI or the Acquia UI.

```bash
rsync -av humscigryphon.prod@web-XXXX.prod.hosting.acquia.com:/mnt/gfs/humscigryphon/sites/SITE/files ~/site-backups/SITE-files-backup_YYYY-MM-DD
rsync -av humscigryphon.prod@web-XXXX.prod.hosting.acquia.com:/mnt/gfs/humscigryphon/sites/SITE/files-private ~/site-backups/SITE-files-backup_YYYY-MM-DD
```


#### 🗄️ Archive the Backup

```bash
tar -czvf ~/site-backups/SITE-files-backup_YYYY-MM-DD.tar.gz ~/site-backups/SITE-files-backup_YYYY-MM-DD
```

#### ☁️ Upload and Clean Up


Upload the file backup to a secure internal storage location as directed by your team. Refer to internal documentation for the correct URL.

Delete local backups after uploading.

---

## ⭐ Best Practices & Final Notes

- Always confirm with H&S whether a site should be decommissioned or deleted.
- Keep backups of both database and files before deletion.
- Communicate with stakeholders before and after site removal.
- Document any manual steps or exceptions for future reference.
- For sensitive or security-related removals, prioritize deletion and secure backup storage.

> 💬 **Note:** If you encounter issues or have questions, reach out to the H&S development team for support.
