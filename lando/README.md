# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

1. [Install Lando](https://lando.dev/download/)
2. Copy `lando/default.lando.yml` to `.lando.yml`.
3. Take the `.loc` domains in the `.lando.yml` file and add them to your `/etc/hosts` file, as shown below:

    ```
      127.0.0.1 africanstudies.suhumsci.loc
      127.0.0.1 amstudies.suhumsci.loc
      127.0.0.1 anthro-net.suhumsci.loc
      127.0.0.1 anthropology.suhumsci.loc
      127.0.0.1 antiracismlab.suhumsci.loc
      127.0.0.1 archaeology.suhumsci.loc
      127.0.0.1 art.suhumsci.loc
      127.0.0.1 artexhibitions.suhumsci.loc
      127.0.0.1 bingschool.suhumsci.loc
      127.0.0.1 biology.suhumsci.loc
      127.0.0.1 biologyvirtualshowcase.suhumsci.loc
      127.0.0.1 bsurp.suhumsci.loc
      127.0.0.1 buddhiststudies.suhumsci.loc
      127.0.0.1 ccsre.suhumsci.loc
      127.0.0.1 ceas.suhumsci.loc
      127.0.0.1 cesta.suhumsci.loc
      127.0.0.1 chemistry.suhumsci.loc
      127.0.0.1 clas.suhumsci.loc
      127.0.0.1 classics.suhumsci.loc
      127.0.0.1 cmbprogram.suhumsci.loc
      127.0.0.1 cmems.suhumsci.loc
      127.0.0.1 cqmd.suhumsci.loc
      127.0.0.1 creativewriting.suhumsci.loc
      127.0.0.1 creees.suhumsci.loc
      127.0.0.1 culture-emotion-lab.suhumsci.loc
      127.0.0.1 datasciencemajor.suhumsci.loc
      127.0.0.1 dennylab.suhumsci.loc
      127.0.0.1 dfetter.humsci.suhumsci.loc
      127.0.0.1 dfetter2022.humsci.suhumsci.loc
      127.0.0.1 dlcl.suhumsci.loc
      127.0.0.1 dsresearch.suhumsci.loc
      127.0.0.1 duboislab.suhumsci.loc
      127.0.0.1 ealc.suhumsci.loc
      127.0.0.1 economics.suhumsci.loc
      127.0.0.1 em1060.suhumsci.loc
      127.0.0.1 english.suhumsci.loc
      127.0.0.1 ethicsinsociety.suhumsci.loc
      127.0.0.1 facultyaffairs-humsci.suhumsci.loc
      127.0.0.1 facultyaffairs-humsci2021.suhumsci.loc
      127.0.0.1 feldman.suhumsci.loc
      127.0.0.1 feminist.suhumsci.loc
      127.0.0.1 finance-humsci.suhumsci.loc
      127.0.0.1 francestanford.suhumsci.loc
      127.0.0.1 gavin-wright.humsci.suhumsci.loc
      127.0.0.1 gavin_wright2022.humsci.suhumsci.loc
      127.0.0.1 gender.suhumsci.loc
      127.0.0.1 globalcurrents.suhumsci.loc
      127.0.0.1 grandtour.suhumsci.loc
      127.0.0.1 gus-humsci.suhumsci.loc
      127.0.0.1 gus-humsci2021.suhumsci.loc
      127.0.0.1 heidi-williams.humsci.suhumsci.loc
      127.0.0.1 heidi_williams2022.humsci.suhumsci.loc
      127.0.0.1 history.suhumsci.loc
      127.0.0.1 hopkinsmarinestation.suhumsci.loc
      127.0.0.1 hs-colorful.suhumsci.loc
      127.0.0.1 hs-design.suhumsci.loc
      127.0.0.1 hs-fcp.suhumsci.loc
      127.0.0.1 hs-sandbox.suhumsci.loc
      127.0.0.1 hs-testing-sandbox.suhumsci.loc
      127.0.0.1 hs-traditional.suhumsci.loc
      127.0.0.1 hsbi.suhumsci.loc
      127.0.0.1 hshr.suhumsci.loc
      127.0.0.1 hsweb-userguide.suhumsci.loc
      127.0.0.1 humanbiology.suhumsci.loc
      127.0.0.1 humanexperience.suhumsci.loc
      127.0.0.1 humanitiescore.suhumsci.loc
      127.0.0.1 humanitiescore2022.suhumsci.loc
      127.0.0.1 humanrights.suhumsci.loc
      127.0.0.1 impact.suhumsci.loc
      127.0.0.1 insidehs.suhumsci.loc
      127.0.0.1 internationalrelations.suhumsci.loc
      127.0.0.1 iranianstudies.suhumsci.loc
      127.0.0.1 iriss.suhumsci.loc
      127.0.0.1 islamicstudies.suhumsci.loc
      127.0.0.1 it-humsci.suhumsci.loc
      127.0.0.1 jewishstudies.suhumsci.loc
      127.0.0.1 language.suhumsci.loc
      127.0.0.1 linguistics.suhumsci.loc
      127.0.0.1 lowe.suhumsci.loc
      127.0.0.1 mathematics.suhumsci.loc
      127.0.0.1 mcs.suhumsci.loc
      127.0.0.1 mediterraneanstudies.suhumsci.loc
      127.0.0.1 memorylab.suhumsci.loc
      127.0.0.1 morrisoninstitute.suhumsci.loc
      127.0.0.1 mrc.suhumsci.loc
      127.0.0.1 mrc2021.suhumsci.loc
      127.0.0.1 mtl.suhumsci.loc
      127.0.0.1 music.suhumsci.loc
      127.0.0.1 oconnell.suhumsci.loc
      127.0.0.1 philit.suhumsci.loc
      127.0.0.1 philosophy.suhumsci.loc
      127.0.0.1 physics.suhumsci.loc
      127.0.0.1 planning-humsci.suhumsci.loc
      127.0.0.1 politicalscience.suhumsci.loc
      127.0.0.1 popstudies.suhumsci.loc
      127.0.0.1 psychology.suhumsci.loc
      127.0.0.1 publicpolicy.suhumsci.loc
      127.0.0.1 religiousstudies.suhumsci.loc
      127.0.0.1 researchadmin-humsci.suhumsci.loc
      127.0.0.1 scl.suhumsci.loc
      127.0.0.1 sgs.suhumsci.loc
      127.0.0.1 shenlab.suhumsci.loc
      127.0.0.1 sitp.suhumsci.loc
      127.0.0.1 siw.suhumsci.loc
      127.0.0.1 sociology.suhumsci.loc
      127.0.0.1 southasia.suhumsci.loc
      127.0.0.1 stanfordsciencefellows.suhumsci.loc
      127.0.0.1 starlab.suhumsci.loc
      127.0.0.1 statistics.suhumsci.loc
      127.0.0.1 sts.suhumsci.loc
      127.0.0.1 suac.suhumsci.loc
      127.0.0.1 swshumsci.suhumsci.loc
      127.0.0.1 swshumsci-sandbox.suhumsci.loc
      127.0.0.1 symsys.suhumsci.loc
      127.0.0.1 tessier-lavigne-lab.suhumsci.loc
      127.0.0.1 texttechnologies.suhumsci.loc
      127.0.0.1 urbanstudies.suhumsci.loc
      127.0.0.1 west.suhumsci.loc
      127.0.0.1 womensleadership.suhumsci.loc
      127.0.0.1 womensleadershipcp.suhumsci.loc
    ```

4. Build your containers: `lando rebuild`
    * Note: You should then see a list a APPSERVER URLS. A green URL signifies the step 3 worked correctly, and you'll be able to access the site in your browser (you'll see errors until you complete all the steps). If you see a red URL, go back to step 3.  TODO: someone with a working proxy system should confirm this.  Can you get green URLs after this step, or will it only work after you have a database?
    * Watch for [`sed` errors](#sed-error-when-docker-uses-virtiofs)
5. Run `lando blt drupal:sync --site=SITE_ALIAS` to pull down a copy of the live database and files for the site you wish to work on (alternatively [pull a db from staging or dev](#syncing-from-staging)). The `SITE_ALIAS` is the site alias and can be found in the `multisites` section of `blt/blt.yml`. In most cases, it matches the name in the local domain, with dashes replaced with underscores (`hs-traditional` → `hs_traditional`).
6. Run `lando drush @[SITE_ALIAS].local uli` to log in as user:1 (Example: `lando drush @music.local uli`).  It will give you a URL like `http://hs-traditional.suhumsci.loc/user/reset/1/12345/abcd9876/login`  This should also run the front-end build.
7. If you have issues, see [Troubleshooting](#troubleshooting).
8. Front-end engineers, return to the main documentation for [front-end build and watch commands](../README.md#builds).

## Common commands

* `lando drush @[SITE_ALIAS].local uli` - Get a link for logging in as an admin user
* `docker ps` - Check that your docker containers are running
* `lando info` - Check your lando config, including a list of domains, URLs, ports, etc.
* `lando drush @[SITE_ALIAS].local cr` - clear cache
* `lando drush @[SITE_ALIAS].local config-export` - export your local database settings
* `lando drush @[SITE_ALIAS].local config-import` - import new database settings to your local.

## Troubleshooting

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `lando drush config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `lando composer install`.
* If you find yourself in a position where starting fresh is your best plan of action, `lando destroy` will completely clear your running lando instances for a clean start.
* If running `lando composer install` results in a timeout while installing a dependency, the default composer timeout for lando can be increased by running `lando composer --global config process-timeout 2000`.

### `sed` error when Docker uses _VirtioFS_
When Docker is configured to use _VirtioFS_ for file sharing, you might get multiple errors like this when running `lando rebuild`:

```
sed: preserving permissions for '/app/docroot/sites/sts/settings/sed7b9pfU': Permission denied
```
or
```
sed: couldn't open temporary file /app/docroot/sites/africanstudies/settings/sed5mM1CH: Permission denied
```

This is caused by [a bug](https://forums.docker.com/t/sed-couldnt-open-temporary-file-xyz-permission-denied-when-using-virtiofs/125473) in the `sed` command that causes incompatibilities with _VirtioFS_. It has been fixed, but the images used by Lando don't have the latest version. To work around it, do the following:

1. Edit `.lando.yml` and comment or remove the following lines:
    ```yaml
    - find /app/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'username' => 'root'/'username' => 'drupal'/g" {}
    - find /app/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'password' => 'password'/'password' => 'drupal'/g" {}
    - find /app/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'host' => 'localhost'/'host' => 'database'/g" {}
    - cp /app/lando/lando.sites.php /app/docroot/sites/local.sites.php
    ```
2. After running `lando rebuild`, execute the lines manually, changing the paths to match the ones on your local machine. If you're on macOS, you also need to alter the options for the `sed` command a bit:

    ```bash
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'username' => 'root'/'username' => 'drupal'/g" {}
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'password' => 'password'/'password' => 'drupal'/g" {}
    find docroot/sites/ -name local.settings.php | xargs -I {} sed -i '' "s/'host' => 'localhost'/'host' => 'database'/g" {}
    cp lando/lando.sites.php docroot/sites/local.sites.php
    ```

## Adding a new site to Lando
1. Copy an existing site's folder in `docroot/sites/` and rename it to the new site's name.
2. Edit the `blt.yml` file within your new site's folder with the corresponding site names. All other files within this folder use variables and don't need any modification.
3. Add the site domain to `.lando.yml` and `/etc/hosts`.
4. Run `lando rebuild -y`

## Considerations for Apple Silicon Macs
Lando/docker initial versions had known compatibility issues with the first ARM-based Macs. Most of these issues have been fixed in the latest version, but if you still have problems, try the following:
1. Edit your `.lando.yml` file and remove all but one or two sites from the `proxy` configuration.
2. Perform the same setup tasks as above.
3. If you need to test more than 1-2 sites at a time you will need to repeat step 1 and run through the full setup process again.

## Setup for local Codeception testing

**NOTE:** This does not work well on ARM-based Mac's. Using Linux is highly recommended.

1. Copy codeception yml for setup.
Copy `lando/default.codeception.yml` to `tests/codeception.yml`.
    * Edit this new `tests/codeception.yml` file and change the `HTTP_HOST`, `uri` and `url` (lines 19, 23, 25, and 28) to be the lando host of the site you are working on locally.
    * For example: hs-colorful would be `hs-colorful.suhumsci.loc` (keep `http://` for the url.)
    * You can also just edit `$db_name` in `lando/default.codeception.yml` by appending the site name.
2. Add local Drush configuration for testing
    * Edit `docroot/sites/default/settings/local.settings.php` database connection to be the connection located in `docroot/sites/sparkbox_sandbox/settings/local.settings.php`.
3. Create `drush/local.drush.yml` and copy this code into that file and change the `sparkbox_sandbox` to the directory name of the site you are working on locally. For example hs-colorful would be `hs_colorful`:

    ```yaml
    # # This file defines drush configuration that applies to drush commands
    # # for the entire application. For site-specific settings, like URI, use
    # # ../docroot/sites/[site]/drush.yml
    drush:
      paths:
        # Load a drush.yml configuration file from the current working directory.
        config:
          - ../docroot/sites/sparkbox_sandbox/local.drush.yml
          - docroot/sites/sparkbox_sandbox/local.drush.yml
          # Allow local global config overrides.
          - local.drush.yml
          - drush/local.drush.yml
        include:
          - '${env.home}/.drush'
          - /usr/share/drush/commands
    ```

4. Change the uri in `/docroot/sites/[site_name]/local.drush.yml` to the uri of the site you are working on locally. For hs-colorful this would be `hs-colorful.suhumsci.loc`

5. Edit ```docroot/sites/default/local.drush.yml``` and change the uri to the uri of the site you are working on. For hs-colorful this would be `hs-colorful.suhumsci.loc`

6. Add the `creds` section to `.lando.yml`. The database should match the site you are currently working on. For example for hs-colorful this would be `database: suhumsci_hs_colorful`.

    ```yaml
    Example:
    services:
      appserver:
        ssl: true
      database: # Override the database that comes in the drupal8
        creds:  # recipe and use it for the /sites/default site.
          user: drupal
          password: drupal
          database: sparkbox_sandbox
    ```

7. Edit `/blt/local.blt.yml` and change the uri, hostname, and database (lines 4, 5 and 8) to the values for the site you are working on. For example, for hs-colorful, these would be `hs-colorful.suhumsci.loc` for the uri and hostname, and `suhumsci_hs_colorful` for the database.

8. Rerun `lando rebuild` to get the new configuration added in step 5.

### To run Codeception tests locally

To run codeception tests run `lando blt codeception --group=install`. Or if you wish to run a single class/method add the annotation in the docblock `@group testme` and then run `lando blt codeception --group=testme`.

## Syncing from Staging

In order to sync from a staging or dev site, you will have to do the following:

1. In `suhumsci/docroot/sites/SITENAME/blt.yml` (`SITENAME` being the site you are working with), change line 10 for remote to: `remote: hs_colorful.stage` or `remote: hs_colorful.dev`.
2. Sync the database as you normally would: `lando blt drupal:sync --site=SITENAME`.

## Configuration for local SimpleSAML authentication

To configure the SimpleSAML module so that you stop seeing the configuration errors in Drupal from that module and also to allow you to login from the /user login page with your Stanford account. (These commands should be run from the root directory.)

1. Run `lando blt sws:keys`
2. Run `lando blt sbsc`
3. Go to the `/simplesamlphp/config` folder and edit the `local.config.php` file.
4. Make sure lines 10,11,12 match the information from your `lando.yml` file for the site you are working on.
    * Example: If you are working on `sparkbox_sandbox` you will want to add `sparkbox_sandbox` in for the host and the dbname on line 10 and update the username and password below to drupal.
5. After you’ve gotten that file up to date, you need to `run lando blt sbsc` once more and then clear your site cache with `lando drush @[site_name] cr` and the error should be gone upon reloading.

**Notes:**

* There are still some slight bugs to work out with SimpleSAML. It will work for log in, but after logging in may throw errors on the login page. This can be resolved by clearing the browser cookies for that site.

* The command for `lando drush @SITENAME.local uli` should still function with or without SimpleSAML configured to log in to the local site, if this is redirecting or not functioning correctly you should ensure the module is enabled or resync the configuration on your local site.

## Other useful links

* [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
* [Drush configuration and aliases](../drush/README.md)
