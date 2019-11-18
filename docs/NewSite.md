# Provisioning a new site

## Choose a final url and machine name
* To keep consistent with site directories, its important to structure the machine name
and the url appropriately.
* For a final URL of `site-name.stanford.edu` the site directory and database should be `site_name`
* For a URL of `site-name.third-domain.stanford.edu` the site directory and database should be `site_name__third_domain`
* Use these in the following steps.

## Establish domains with Stanford
1. Add the domains to the [NetDB record](https://netdb.stanford.edu/node_info?name=swshumsci.stanford.edu&history=%252Fqsearch%253Fsearch_string%253Dswshumsci%2526search_type%253DNodes)
  * Keep with the pattern: [site-name]-dev, [site-name]-stage, and [site-name]-prod
1. Wait for the DNS Refresh. This occurs every half hour at :05 and :35 past the hour.
1. Ensure the Vhost points to Acquia by pinging the url. `ping newvhost.stanford.edu`

## Setup Domains and SSL Certs in Acquia
1. Disable the shield module for all sites on the dev and test environments. This ensures the the SSL certs can be verified when they are issued.
  * `blt drupal:module:uninstall shield dev`
  * `blt drupal:module:uninstall shield stage`
1. Add the new domains to each of the Acquia environments (dev, stage, and prod). You can do this through the Acquia UI, or by using `blt humsci:add-domain`. For example:
  * `blt humsci:add-domain dev [site-name]-dev.stanford.edu`
  * `blt humsci:add-domain test [site-name]-test.stanford.edu`
  * `blt humsci:add-domain prod [site-name]-prod.stanford.edu`
1. Re-issue the SSL certs for each environment. For example:
  * `blt humsci:letsencrypt:add-domain --domains=[site-name]-dev.stanford.edu -- dev`
  * `blt humsci:letsencrypt:add-domain --domains=[site-name]-stage.stanford.edu -- test`
  * `blt humsci:letsencrypt:add-domain --domains=[site-name]-prod.stanford.edu -- prod`
1. Get the newly built SSL certs for each environment (`blt humsci:letsencrypt:get-cert [environment]`) and add them to their corresponding [Acquia Dashboards](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265865-23a85077-2967-41a4-be22-a84c24e0f81a/ssl) by clicking "Install SSL Certificate" and filling out the details.
1. Once added, activate the new certs for each environment in the Acquia dashboard (which will deactivate the previously active certs).
1. Confirm it worked by browsing to a new url and verifying that it has a valid certificate and is directed to the default site.

## Site Directory and settings
1. Create a new database in [Acquia dashboard](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265866-23a85077-2967-41a4-be22-a84c24e0f81a/databases). Adding a database to one environment adds one to all environments.
   * Use the database name as defined above.
1. Execute blt command `blt recipes:multisite:init` and answer questions as desired.
   * Machine name of the site should match the final vhost to be desired. Use the same name as the database above.
   * "remote drush alias" should be [machine_name].prod
1. Add the machine name to the [blt settings](../blt/blt.yml) for the `multisites` array.
1. Edit the new drush alias file in [drush/sites](../drush/sites) to add configuration for dev, test and prod environments.
   * Use the [default.yml](../drush/sites/default.site.yml) file as a template

## Deploy and test
1. Checkout a new branch with all the new files and commit.
1. Deploy to Acquia with `blt deploy`, do not create a tag
1. In Acquia choose the deployed branch for the development environment.
1. Ensure the new site is recognized:
    * Check drush status `drush @[site-name.dev] st`
    * verify the database name is something like `swshumscidb######`
    * verify the "Site URI" is something like `[site-name]-dev.stanford.ed`
1. Install a new site `drush @[site-name].dev si config_installer -y`
1. Disable config_ignore to ensure full install state `drush @[site-name].dev pmu config_ignore`
1. Import all the configs again `drush @[site-name].dev cim -y`
1. Visit the site and validate login and installation was successful
1. Copy that database to stage and production environments. (This prevents deployment & testing errors)
