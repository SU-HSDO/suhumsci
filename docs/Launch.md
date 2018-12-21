# Launch Process

A "Launch" is any change to a domain that can impact visitors.

## Several days before
1. Schedule the launch with the site owner including a content freeze the day of.
1. Add the new domain to the [production environment](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265865-23a85077-2967-41a4-be22-a84c24e0f81a/domains)
1. Add the new domain to the SAML configuration [https://spdb.stanford.edu/spconfigs/3931/edit](https://spdb.stanford.edu/spconfigs/3931/edit)
1. Ensure the new domain is in the "Trusted Domains" for the SAML setup:
   1. Execute `blt humsci:keys` to get the keys and SAML configuration
   1. Modify the file [acquia_configs.php](../keys/saml/acquia_configs.php) to ensure the `$config['trusted.url.domains']` array includes the new domain
   1. If any changes were made, push the keys back to Acquia `blt humsci:keys:send prod`
1. Schedule the release of the domain with the appropriate people.
   * Check who owns vhost and that it can be changed by SWS or AS Central Infrastructure Applications (CIA) (using one of the following methods):
     1. `host foo.stanford.edu`
     1. `remctl tools proxy showdest foo.stanford.edu`
     1. Use StanfordWhat
   * Search IT Services Virtual Hosting
   * If the vhost points to www-v6.stanford.edu or proxy-service.best.stanford.edu, then CIA can release it. 
   * If it points to sites-lb-stanford.edu, then Shea or John can change the vhost. It does not require a SNOW request to CIA. See "Changing Vhosts on sites-lb", below.
   * Otherwise, we have to contact the owner of the record in NetDB (see StanfordWhat ) and have them release it. If the owner agrees to release the vhost, send an email to the owner and CC Malkiat Singh and let them sort it out.
1. Add necessary redirects to the site.

# Launch Day
1. Wait for the Vhost release
1. Add the new Vhost to the [NetDB record](https://netdb.stanford.edu/node_info?name=swshumsci.stanford.edu&history=%252Fqsearch%253Fsearch_string%253Dswshumsci%2526search_type%253DNodes)
1. Wait for the DNS Refresh. This occurs every half hour at :05 and :35 past the hour.
1. Ensure the Vhost points to Acquia by pinging the url. `ping newvhost.stanford.edu`
1. Add the domain to the LetsEncrypt Certificate `blt humsci:letsencrypt:add-domain prod`
1. Add the new cert contents into [Acquia Dashboard](https://cloud.acquia.com/app/develop/applications/23a85077-2967-41a4-be22-a84c24e0f81a/environments/265865-23a85077-2967-41a4-be22-a84c24e0f81a/ssl) `blt humsci:letsencrypt:get-cert prod`. All 3 cert files have to be added.
1. Activate the new Cert.
1. Execute the blt command `blt humsci:launch-site [site_machine_name]` and enter the new site domain. This command will
configure necessary site configuration and clear caches.
1. Submit the site to Google for indexing: [steps to index](https://asconfluence.stanford.edu/confluence/display/SWS/Submit+sitemap+to+Google+Webmaster+tools)
