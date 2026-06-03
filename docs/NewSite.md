# Provisioning a new site

## Overview

This guide covers the complete process for provisioning a new H&S Stanford Drupal site on the Acquia hosting platform. The process involves coordinating with Stanford IT (NetDB/DNS), configuring Acquia environments, managing local code and drush configuration, deploying code changes, and finally installing the site database.

## Prerequisites

See [H&S Development Requirements](https://stanfordits.atlassian.net/wiki/spaces/HSDO/pages/1075052547)

## Step 1: Determine the URL and site alias

To keep consistent with site directories and databases, structure the machine name and URL appropriately.

**URL to alias mapping:**
- One underscore (`_`) becomes a dash (`-`)
- Two underscores (`__`) become a dot (`.`)

**Examples:**
- Final URL: `site-name.stanford.edu` → alias: `site_name`
- Final URL: `site-name.third-domain.stanford.edu` → alias: `site_name__third_domain`

Use the site alias consistently in all following steps for the machine name, database name, and drush configuration.

## Step 2: Establish domains with Stanford (NetDB)

Create domain aliases for all three environments in [NetDB](https://netdb.stanford.edu/). These aliases must be added to the [CDN NetDB node](https://netdb.stanford.edu/node_info?name=stanfordedu.edgesuite.net) (`stanfordedu.edgesuite.net`).

Create three aliases following this pattern:
- `[site-alias]-dev`
- `[site-alias]-stage`
- `[site-alias]-prod`

**Example:** For a site with alias `politicalscience`:
- `politicalscience-dev`
- `politicalscience-stage`
- `politicalscience-prod`

**To create the aliases in NetDB:**
1. Click the "Modify" link on the CDN node
2. Enter "3" in the "Count" field
3. Click "Add Alias"
4. Enter the three new aliases in the empty fields
5. Click "Save"

**Verify DNS propagation:**
- DNS changes push out every 30 minutes at the :05 minute mark
- Test with: `ping -c 4 [site-alias]-prod.stanford.edu`
- Check propagation time with: `dig dns-generation-time.stanford.edu txt | grep "ANSWER SECTION" -A 1`

## Step 3: Create a development branch

Check out the latest development branch (the current major version *.x branch) and update your local code:

```bash
git checkout 12.x
git fetch && git pull
composer install
```

Create a local feature branch for this provisioning work:

```bash
git checkout -b [TICKET(s)]--provision-[site(s)]
```

**Branch naming examples:**
- `HSD8-123--provision-mysite`
- `HSD8-123-124--provision-site1-site2`

## Step 4: Add domains and database to Acquia

All commands below are issued locally with ACLI but execute remote actions on Acquia.

**Add domains to each Acquia environment (dev, test, prod):**

```bash
acli api:environments:domain-create humscigryphon.dev --hostname="[SITENAME]-dev.stanford.edu"
acli api:environments:domain-create humscigryphon.test --hostname="[SITENAME]-stage.stanford.edu"
acli api:environments:domain-create humscigryphon.prod --hostname="[SITENAME]-prod.stanford.edu"
```

**Create a new database in Acquia:**

Using the Acquia dashboard: [HumSci Gryphon Application](https://cloud.acquia.com/a/applications/60ee2ebb-94f3-415d-a289-c23889ecec18)

Or via ACLI (application UUID for HumSci Gryphon: `60ee2ebb-94f3-415d-a289-c23889ecec18`):

```bash
acli api:applications:database-create 60ee2ebb-94f3-415d-a289-c23889ecec18 [SITENAME]
```

Note: Creating a database in one Acquia environment automatically creates it in all three (dev, test, prod).

## Step 5: Site directory and drush configuration

### Run the new site command

Execute the drush command to scaffold the new site configuration:

```bash
drush sws:multisite:new-site [SITENAME]
```

Example: `drush sws:multisite:new-site appliedphysics`

This command generates the necessary site files and updates the multisites configuration.

### Verify drush configuration

**Check that the site was added to the multisites array:**

Open [drush/drush.yml](../drush/drush.yml) and verify the new site appears under the `multisites:` section. The `drush sws:multisite:new-site` command automatically generates the necessary drush alias files in [drush/sites](../drush/sites).

## Step 6: Create and merge the pull request

1. Commit your changes locally
2. Push the branch to origin
3. Create a Pull Request with the base branch set to the **latest release branch** (not develop)
4. Assign the PR for review

**Note:** You cannot proceed to the next steps until this PR is merged and deployed to a Acquia environment (typically staging first).

## Step 7: Configure CDN / Akamai aliases

This step must be completed before the provisioned domains will work correctly.

1. Log in to [Akamai Control Center](https://control.akamai.com/)
2. Find the `stanford-default` CDN property
3. Create a new property version from the currently active version
4. Add the new environment aliases to the `humscigryphon` hostname rule:
   - `[SITENAME]-dev.stanford.edu`
   - `[SITENAME]-stage.stanford.edu`
   - `[SITENAME]-prod.stanford.edu`
5. Activate the new version on staging first
6. Post an update in `#sws-acquia-waf` when activating the CDN change
7. After staging activation is successful, activate the same version on production
8. Confirm the new CDN version is active on both staging and production

## Step 8: Deploy and verify site configuration

Once the code PR has been merged and deployed to an Acquia environment (typically staging or production):

1. **Verify the site is recognized in Acquia:**
   ```bash
   drush @[SITENAME].test st
   ```
   - Verify the database name is something like `swshumscidb######`
   - Verify the "Site URI" is something like `[SITENAME]-stage.stanford.edu`

2. **Check all environment aliases are working:**
   ```bash
   drush @[SITENAME].dev st
   drush @[SITENAME].test st
   drush @[SITENAME].prod st
   ```

## Step 9: Install the site

This step cannot be completed until the provisioned code PR has been merged and deployed to production.

1. **Check the site URLs** to verify they haven't already been installed:
   - Visit `[SITENAME]-dev.stanford.edu`
   - Visit `[SITENAME]-stage.stanford.edu`
   - Visit `[SITENAME]-prod.stanford.edu`
   - If you see a Drupal install page, proceed with installation
   - If you see a normal H&S Stanford Drupal site, installation is already complete or pointers aren't working correctly

2. **Install the site with the H&S profile:**
   ```bash
   drush @[SITENAME].prod si su_humsci_profile -y
   ```

3. **Verify installation:**
   - Visit the site in a browser
   - Validate that login works
   - Verify the H&S Stanford theme is properly loaded

## About the Installation Profile

New sites are installed using the `su_humsci_profile`.

Configurations in `su_humsci_profile` are overridden by those in `config/default` during the site-install process. The profile is valuable for:
- Adding update hooks for new multisites
- Providing placeholder content for new sites
- Defining initial site-specific settings

## Note on Acquia environment naming

Acquia uses "test" as the CLI alias for the "staging" environment. This can be confusing when referencing environments—always use "test" in drush aliases and ACLI commands, but refer to it as "staging" in documentation and communications.
