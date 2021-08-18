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
1. Add the new domains to each of the Acquia environments (dev, stage, and prod). You can do this through the Acquia UI, or by using `blt humsci:add-domain`. For example:
    * `blt humsci:add-domain dev [site-name]-dev.stanford.edu`
    * `blt humsci:add-domain test [site-name]-stage.stanford.edu`
    * `blt humsci:add-domain prod [site-name]-prod.stanford.edu`
1. Confirm it worked by browsing to one of the new domains and verifying that it has a valid certificate and is directed to the default site.

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
Once the code with the new files is merged and deployed to an Acquia environment (like staging), we can setup the site database:

1. Ensure the new site is recognized in the Acquia environment:
    * Check drush status `drush @[site-name.stage] st`
    * verify the database name is something like `swshumscidb######`
    * verify the "Site URI" is something like `[site-name]-stage.stanford.edu`
1. Install a new site `drush @[site-name].stage si su_humsci_profile -y`
1. Visit the site and validate login and installation was successful
1. Copy that database to stage and production environments. (This prevents deployment & testing errors)

### Notes on the Installation Profile

New sites are launched using the `su_humsci_profile` (as shown above).

Configs in `su_humsci_profile` aren't really relevant because they get overridden by the config/default ones during the
site-install process. `su_humsci_profile` is valuable because it serves as place to add update hooks and placeholder
content new multisites.
