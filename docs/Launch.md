# Site Launch Process

> **Security & Confidentiality Notice:** This document contains instructions for managing Stanford H&S sites on internal systems. Developers with access to Acquia Cloud, NetDB, and SWS infrastructure should follow these procedures carefully. When discussing launches or sharing outputs, ensure access credentials and sensitive system details remain confidential.

## Introduction

A "Launch" is any change to a domain that can impact visitors. This guide covers the complete site launch workflow, from pre-launch checks through post-launch verification and cleanup.

## Requirements
*See [H&S Development Requirements](DevelopmentRequirements.md)*

## Understanding Aliases

Several different aliases are referenced throughout this guide:

* **URL Alias** — the chosen live domain/site URL (e.g., `URL_ALIAS.stanford.edu`)
* **Site Alias** — the site name used when provisioning the site (in `docroot/sites/SITE_ALIAS`), also used for **Drush Aliases** (e.g., `DRUSH_ALIAS.prod`)
  * Site Alias uses underscores `_` instead of dashes `-`, and double underscores `__` for periods `.`
  * Example: URL Alias `my-site` → Site Alias `my_site`

## Pre-Launch

### Determine alias (live domain) availability

Search for the desired URL Alias in [**NetDB**](https://netdb.stanford.edu/) (requires Stanford network access or VPN):

* If there are no results, proceed — the alias is available.
* If the alias is on the `stanfordedu.edgesuite.net` NetDB node, it points to the SWS WAF / CDN — you're ready to proceed.
* If the alias is on the `stanford.dns.bl.ink` NetDB node, it's in the [**Vanity URL**](https://vanityurl.stanford.edu/) service. Verify H&S owns the domain and request they add it to the `hsweb:sws-vanity-url-shared` workgroup for SWS access.
* If the alias exists on another NetDB node, a site is already using it. Verify H&S ownership and confirm you have NetDB modification access before proceeding.

**When in doubt**, check with H&S and fellow SWS team members to confirm alias ownership.

## Launch

### Prep codebase

Navigate to your local `suhumsci` repo:

```bash
cd PATH_TO/suhumsci
git checkout RELEASEBRANCH
git fetch && git pull
composer install
```

### Make changes to aliases in NetDB

NetDB refreshes DNS at :05 and :35 past each hour. Start making changes 15–20 minutes before the next refresh to minimize delay.

**Note:** Requires Stanford network access or VPN.

#### Remove current production alias

Based on availability checks above:

* If the alias didn't appear in search results or was on the SWS WAF node, no action needed.
* If the alias is on the Vanity URL node, delete it from the [**Vanity URL**](https://vanityurl.stanford.edu/) tool.
* If the alias is on another NetDB node, click modify, find the alias in the field list, clear it, and save.

#### Add production URL alias to the WAF / CDN node

* Open the `stanfordedu.edgesuite.net` NetDB node
* Click the modify button
* To the right of the first alias field, enter "1" in the Count field and click "Add Alias"
* Enter the new URL Alias in the empty field that appears
* Save the node

**Warning:** This node manages 1,000+ live websites. Double-check all changes before saving; discard and restart if unsure.

### Add domain to Acquia

Add the domain to the Acquia production environment using `acli` (if this is a site transfer, the domain may already exist):

```bash
acli api:environments:domain-create humscigryphon.prod --hostname="URL_ALIAS.stanford.edu"
```

Alternatively, use the [**Acquia UI**](https://cloud.acquia.com/a/applications/all) (requires Acquia access):
* Open the `HumSci Gryphon` app
* Go to the prod environment
* Click "Domains" in the left navigation
* Click "Add Domain" and enter the new URL

### Configure WAF / CDN

Configure the domain in the Stanford WAF / CDN (Akamai) to route traffic to the H&S origin servers.

**Note:** Requires access to the Stanford Default property in Akamai.

1. **Create a new WAF / CDN version**
   * In Akamai, create a new configuration version based on the currently active version
   * Version numbers are assigned automatically by Akamai

2. **Add the domain alias**
   * Add the new URL alias to the H&S origin configuration
   * If migrating other domains during this launch, add them now as well

3. **Add version notes**
   * Document what aliases were added (e.g., "Added publicpulse live alias")
   * Include any other domains migrated in this version

4. **Activate the new version**
   * Activate the new version on staging first
   * Communicate the activation in the internal WAF / CDN Slack channel
   * Once staging is confirmed, activate on production
   * Confirm successful activation in Slack

### Domain pointing workaround

If the site alias does not match the desired live URL (e.g., site installed as `docroot/sites/mysite` but live URL is `my-site.stanford.edu`), use this workaround:

Skip this section if the site alias matches the desired live URL.

#### Workaround steps

**Important:** PHP syntax errors in this file will cause fatal errors across all sites on the stack. Type and test the code locally first.

* SSH into the server via drush:
  ```bash
  drush @DRUSH_ALIAS ssh
  ```

* Navigate to the workaround file directory:
  ```bash
  cd /mnt/files/humscigryphon.prod
  ```

* Edit the `sites.php` file (create it if it doesn't exist):
  ```bash
  nano sites.php
  ```

* Add the PHP code to point the domain to the correct site:
  ```php
  $sites['SITE_URL'] = 'SITE_PATH_ALIAS';
  ```
  Example: `$sites['lowe.stanford.edu'] = 'lowe2022';`

* Save (Ctrl+O, then Ctrl+X in Nano)

* Clear varnish cache for the URL to expedite resolution

#### Permanent sites.php steps

1. Copy the `key => value` pair from the workaround
2. Add it to the [custom URL pointing section](https://github.com/SU-HSDO/suhumsci/blob/develop/docroot/sites/sites.php) of `docroot/sites/sites.php`
3. Create a PR to commit the change permanently

**Why a workaround?** The `sites.php` file uses automatic routing to point `URL_ALIAS.stanford.edu` to the matching site alias. The workaround file overrides this behavior when the URL doesn't match the alias, allowing immediate domain changes without a full code deployment.

### Wait for the URL change to resolve

DNS should resolve 5–10 minutes after the NetDB refresh (:05/:35 every hour). Monitor the change while proceeding with SSL setup and the launch-site command to minimize downtime.

### Run launch-site command

Execute the launch command for the site:

```bash
drush humsci:launch-site --site=SITE_ALIAS
```

**Alias conversion reminder:**
* Dashes `-` become underscores `_`
* Periods `.` become double underscores `__`

Examples:
```bash
drush humsci:launch-site --site=creees
drush humsci:launch-site --site=finance_humsci
```

The script will prompt for the new domain. Press Enter if the URL is correct, or enter it manually.

#### Verify canonical URL redirect

The `-prod` URL for the site should redirect to the live domain:

* Example: `mysite-prod.stanford.edu` → `mysite.stanford.edu`
* If redirect doesn't work, try a cache-busting query string: `mysite.stanford.edu?foo=bar`
* If still failing, clear Drupal and Varnish caches

### Check ticket

Review the launch ticket for any site-specific requirements or caveats.

### Setup Vanity URLs

If additional Vanity URLs are requested, use the [**Vanity URL**](https://vanityurl.stanford.edu/) tool:

* Click "Requested New Subdomain" to create a new Vanity URL
* If the Vanity URL is an old site alias from a transfer, you may need to remove it from NetDB again

### Setup redirects

**Note (2024-04-15):** Redirect imports were heavy during the D7-to-D8 switchover. This section is mostly relevant only for legacy migrations; most current launches do not require redirect imports.

#### Download spreadsheet

* Obtain the Google Sheet URL redirects from the launch ticket
* Download as CSV
* Open in a text editor
* Find and replace to remove the base URL from the "Old url" column (keep trailing slash):
  * Example: `http://mysite.stanford.edu/new/path` → `/new/path`

#### Clean up spreadsheet

Review for common issues:

* Remove any `node/<id>` URLs
* Remove redirects where source = destination
* Redirect to `/` (home page) is acceptable
* External site redirects are acceptable
* Replace `<front>` with `/`
* Remove `-prod` TLD from absolute `-prod` URLs, leaving `/`
* Missing new URLs are acceptable
* Fix anything that looks incorrect; don't be overly strict

Save and verify formatting in a text editor before proceeding.

#### Import spreadsheet

Navigate to the prod site (`ALIAS-prod.stanford.edu` or the live URL if already switched):

* Log in to the site
* Go to **Configuration** → **Search and Metadata** → **URL Redirects** → **Import**
* Select the CSV file (no other options needed)
* Run the import

**Note:** If an import fails because a page was renamed, investigate and fix if possible; otherwise note it and report in the ticket.

### Confirm site ownership with Google

**Skip this step** if the site is 100% behind CardinalKey (e.g., internal intranet).

1. Log into [Google Search Console](https://search.google.com/) with the SWS Developers Google account (uses TFA)
   * **Important:** Ensure you're logged into the SWSdevelopers account, not your personal account
   * Credentials: [SWS External Account Credentials](https://asconfluence.stanford.edu/confluence/display/SWS/External+Account+Credentials) (requires Confluence access)

2. Add the property:
   * Click "Add Property" (or a form will appear with Domain/URL prefix options)
   * Enter the live URL under the URL prefix field
   * Use HTTPS protocol

3. Verify ownership via HTML tag:
   * Choose the "HTML Tag" option for verification
   * If the site shows as already verified, copy the verification tag from the source code (look for a `<meta>` element named `google-site-verification`)
   * Copy the tag value from `content="<tag_value>"`

4. Set the verification tag via drush:
   ```bash
   drush @SITENAME.prod cset metatag.metatag_defaults.global tags.google_site_verification [tag_value]
   ```

   Example:
   ```bash
   drush @SITENAME.prod cset metatag.metatag_defaults.global tags.google_site_verification tUJ214wF9k79KCsAn5wIOyOFR2eH0RlBANimm5MCFfU -y && drush @SITENAME.prod cr
   ```

5. Rebuild Drupal cache and verify in Google Console (retry with cache clear if needed)

6. Add sitemap to Google:
   * Open the property in Google Search Console
   * Click "Sitemaps"
   * Add `SITE_URL/sitemap.xml`
   * Verify the sitemap opens and contains valid links
   * If links are broken, rebuild the sitemap and recheck:
     ```bash
     drush @DRUSH_ALIAS.prod xmlsitemap:rebuild
     ```

### Clean up previous site (if exists)

**Note (2024-04-15):** This section was more relevant during D7-to-D8 switchovers; current cleanup needs are minimal.

**Important:** Use the correct alias — easy to confuse old/new when on the same stack.

If replacing an old site, disable its canonical URL redirect and enable nobots:

```bash
drush @DRUSH_ALIAS.prod cset domain_301_redirect.settings enabled 0 -y && \
drush @DRUSH_ALIAS.prod en nobots -y && \
drush @DRUSH_ALIAS.prod sset nobots 1 -y && \
drush @DRUSH_ALIAS.prod cr
```

Or run commands individually:

```bash
drush @DRUSH_ALIAS.prod cset domain_301_redirect.settings enabled 0
drush @DRUSH_ALIAS.prod en nobots
drush @DRUSH_ALIAS.prod sset nobots 1
```

### Inform H&S

Update the launch ticket and post in the **#hs-sws** Slack channel (requires Stanford Slack access):

> The **SITENAME** site has launched successfully: **SITEURL**
> - All redirects imported successfully (or note any issues)
> - The previous site has been set to nobots: **OLDSITEURL**
