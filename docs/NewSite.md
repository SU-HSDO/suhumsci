# Provisioning a new site

## Establish domains with Stanford
1. Add the domains to the [NetDB record](https://netdb.stanford.edu/node_info?name=swshumsci.stanford.edu&history=%252Fqsearch%253Fsearch_string%253Dswshumsci%2526search_type%253DNodes)
    * Keep with the pattern: [sitename]-dev, [sitename]-stage, and [sitename]-prod
1. Add the new domains to the SAML configuration [https://spdb.stanford.edu/spconfigs/3931/edit](https://spdb.stanford.edu/spconfigs/3931/edit)
   * Copy and paste the last line of the xml, change the domain and the `index` number
1. Wait for the DNS Refresh. This occurs every half hour at :05 and :35 past the hour.
1. Ensure the Vhost points to Acquia by pinging the url. `ping newvhost.stanford.edu`

## Site Directory and settings.
1. Create a new database in [Acquia dashboard](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265866-23a85077-2967-41a4-be22-a84c24e0f81a/databases). Adding a database to one environment adds one to all environments.
1. execute blt command `blt recipes:multisite:init` and answer questions as desired. 
   * Machine name of the site should match the final vhost to be desired.
   * "remote drush alias" should be [machine_name].prod 
   * During that command, it will ask to add domains. This is optional but highly suggested. This will prompt to add 
     domain for each environment and it will initiate the new SSL certificate.
1. Get the newly built SSL certs for each environment `humsci:letsencrypt:get-cert [environment]` and add them to [Acquia Dashboard](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265865-23a85077-2967-41a4-be22-a84c24e0f81a/ssl)
1. From the database that was set up in Acquia, grab the PHP snippet and add it to the bottom of the new settings.php
   but above the blt.settings.php require statement.
1. Add the machine name to the [blt settings](../blt/blt.yml) for the `multisites` array.
1. Add the domains to [acquia_configs.php](../keys/saml/acquia_configs.php)
   *. Execute `blt humsci:keys` to get the keys and SAML configuration
   *. Modify the file [acquia_configs.php](../keys/saml/acquia_configs.php) to ensure the `$config['trusted.url.domains']` array includes the new domain
   *. If any changes were made, push the keys back to Acquia `blt humsci:keys:send prod`
1. Edit the new drush alias file in [drush/sites](../drush/sites) to add configuration for dev, test and prod environments.
   * Use the [default.yml](../drush/sites/default.site.yml) file as a template

## Deploy and test
1. Checkout a new branch with all the new files and commit.
1. Deploy to Acquia with `blt deploy`, do not create a tag
1. In Acquia choose the deployed branch for the development environment.
1. Ensure the new site is recognized:
    * Check drush status `drush @[sitename.dev] st`
    * verify the database name is something like `swshumscidb######`
    * verify the "Site URI" is something like `[newsite]-dev.stanford.ed`
1. Install a new site `drush @[newsite].dev si config_installer -y`
1. After installation, import all the configs again `drush @[sitename].dev cim -y`
1. Visit the site and validate login and installation was successful
1. Copy that database to stage and production environments. (This prevents deployment & testing errors)
