# Lando Setup

If you want to use [Lando](https://lando.dev/) for local development, here are some basic steps for getting set up.

_Prerequisite: Make sure you have added your SSH key in Acquia cloud, and that it's saved in your `~/.ssh folder._

1. [Install Lando](https://lando.dev/download/).
    * Apple M1/Silicon Users will need to pay special attention to the version of Lando and Docker they install for proper functionality. "If you have a new Apple Silicon based Mac then choose the arm64 DMG from Lando."
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
    * Note: After running `lando rebuild` you should see a list a APPSERVER URLS. A `green` URL signifies the `.loc` domain has been added to your `/ect/hosts` file. If you see a `red` URL, go back to step 3 and add the `.loc` domain to your `/ect/hosts` file.
5. Run `lando blt drupal:sync --site=SITENAME` to pull down a copy of the database and files for the site you wish to work on.
6. Run `lando info`, and browse to the url for your multisite.

## Adding a new site to lando
1. Copy and existing site's folder in `docroot/sites/` and rename the folder to the new site.
2. Edit the `blt.yml` file within your new sites folder with the corresponding site names.
3. All other files within this folder are variable. All you need to do is run `lando rebuild -y`

## Setup for local Codeception testing

1. Copy codeception yml for setup.
Copy `lando/default.codeception.yml` to `tests/codeception.yml`.
2. Add local Drush configuration for testing
    * Edit `docroot/sites/default/settings/local.settings.php` database connection to be the connection located in `docroot/sites/sparkbox_sandbox/settings/local.settings.php`.

    * Create `drush/local.drush.yml`

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

3. Ensure your `.lando.yml` file default database is setup with `sparkbox_sandbox` as your default db.

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

### To run Codeception tests locally

To run codeception tests run `lando blt codeception --group=install`. Or if you wish to run a single class/method add the annotation in the docblock `@group testme` and then run `lando blt codeception --group=testme`.

## Syncing from Staging

In order to sync from a staging or dev site, you will have to do the following:

1. In `suhumsci/docroot/sites/hs_colorful/blt.yml` (or whichever relevant site you are working with), change line 10 for remote to: `remote: hs_colorful.stage` or `remote: hs_colorful.dev`.
2. Sync the database as you normally would: `lando blt drupal:sync --site=[my-multisite]`.

## Configuration for local SimpleSAML authentication

To configure the SimpleSAML module so you stop seeing the configuration errors in Drupal from that module and also to allow you to login from the /user login page with your Stanford account. (These commands should be run from the root directory.)

1. Run `lando blt sws:keys`
2. Run `lando blt sbsc`
3. Go to the the `/simplesamlphp/config` folder and edit the local.config.php file.
4. Make sure lines 10,11,12 match the information from your `lando.yml` file for the site you are working on.
    * Example: If you are working on `sparkbox_sandbox` you will want to add `sparkbox_sandbox` in for the host and the dbname on line 10 and update the username and password below to drupal.
5. After you’ve gotten that file up to date, you need to run lando blt sbsc once more and then clear your site cache with `lando drush @[site_name] cr` and the error should be gone upon reloading.

**Notes:**

* There is still some slight bugs to work out with SimplsSAML’s login but it will work for login, but after login may throw errors on the login page, this can be resolved by clearing the browser cookies for that site.

* The command for `lando drush uli` should still function with or without SimpleSAML configured to login to the local site, if this is redirecting or not functioning correctly you should ensure the module is enabled or resync your configuration on your local site.

## Common commands

* `lando drush uli` - Get a link for logging in as an admin user
* `docker ps` - Check that your docker containers are running
* `lando info` - Check your lando config
* `lando drush cr` - clear cache
* `lando drush config-export` - export your local database settings
* `lando drush config-import` - import new database settings to your local.

Utilizing these commands with specific sites in your multisite setup looks like this: `lando drush @[]my-multisite] cr`.

## Troubleshooting

### Importing Configuration

* If you run into issues importing new config files try running the command with the partial flag: `lando drush config-import --partial`.
* If the partial flag doesn't work, you may be missing a dependency. Re-sync your whole database, then run `lando composer install`.
* If you find yourself in a position where starting fresh is your best plan of action, `lando destroy` will completely clear your running lando instances for a clean start.
* If running `lando composer install` results in a timeout while installing a dependency, the default composer timeout for lando can be increased by running `lando composer --global config process-timeout 2000`.

## Other useful links

* [Lando Drupal 8 docs](https://docs.lando.dev/config/drupal8.html)
* [Drush configuration and aliases](../drush/README.md)
