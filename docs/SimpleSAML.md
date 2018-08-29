# BLT SAML Setup

## Initialize simple saml with BLT
* blt simplesamlphp:init
* blt simplesamlphp:build:config

## Create certs
* openssl req -x509 -sha256 -nodes -days 3652 -newkey rsa:2048 -keyout saml.pem -out saml.crt
* put certs on acquia /mnt/gfs/[site].[environment]/nobackup/saml
    * /mnt/gfs/[site].[environment]/nobackup/saml/saml.cert
    * /mnt/gfs/[site].[environment]/nobackup/saml/saml.pem
* see [Acquia Documentation on this directory](https://docs.acquia.com/acquia-cloud/manage/files/system-files/private/)

## Acquia Configs
* Change the `$ah_options` array as follow:
```
$ah_options = [
  'database_name' => '[site]',
  'session_store' => [
    'prod' => 'database',
    'test' => 'database',
    'dev'  => 'database',
  ],
];
```
* Create a secret salt `tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo` and put in acquia_config.php
* Create a unique password and put in acquia_config.php 
* Protect the saml admin pages with:
```
$config['admin.protectindexpage'] = true;
$config['admin.protectmetadata'] = true;
```
* Prevent varnish from caching. Add the snippet to acquia_config.php
```
// Prevent Varnish from interfering with SimpleSAMLphp.
// SSL terminated at the ELB/balancer so we correctly set the SERVER_PORT
// and HTTPS for SimpleSAMLphp baseurl configuration.
$protocol = 'http://';
$port = ':80';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $_SERVER['SERVER_PORT'] = 443;
  $_SERVER['HTTPS'] = 'true';
  $protocol = 'https://';
  $port = ':' . $_SERVER['SERVER_PORT'];
}
```
* Tell SAML to translate the urn keys. Add snippet to acquia_config.php
```
$config['authproc.sp'] = array(
  10 => array(
    'class' => 'core:AttributeMap', 'removeurnprefix', 'oid2name',
  ),
  90 => 'core:LanguageAdaptor',
);
```
* Tell saml about the authorized domains
```
$config['trusted.url.domains'] = [
    'my-site-dev.stanford.edu',
    'my-site-prod.stanford.edu',
];
```
## Authsources
* Set the entityID ot the production url `'entityID' => 'https://{site-prod}.stanford.edu',`
* Set the ipd in the `default-sp` array. `'idp' => 'https://idp.stanford.edu/',`
* Tell the default-sp to use the certs. in the `default-sp` array add
```
'privatekey' => '/mnt/gfs/[site].[environment]/nobackup/saml/saml.pem',
'certificate' => '/mnt/gfs/[site].[environment]/nobackup/saml/saml.crt'
```

# Move configs to acquia.
* Copy simplesamlphp/config/acquia_config.php onto acquia /mnt/gfs/[site].[environment]/nobackup/saml
* Replace all contents in the acquia_config.php with the snippet below
```
if (file_exists('/mnt/gfs/[site].[environment]/nobackup/saml/acquia_configs.php')) {
  include '/mnt/gfs/[site].[environment]/nobackup/saml/acquia_configs.php';
}
```

#Split config and functions. (Needed for simplesamlphp 1.15+)
* With the new acquia_configs.php file on acquia server we need to split the configuration changes and the functions
* Create a new file `/mnt/gfs/[site].[environment]/nobackup/saml/acquia_functions.php`
* Move all function from `/mnt/gfs/[site].[environment]/nobackup/saml/acquia_configs.php` into the new file.
* Include that file with `include_once('acquia_functions.php');` at the top of the acquia_configs.php file.

# Meta Data
* After configs are placed on acquia server, do a blt deploy.
* Go to [site]dev.prod.acquia-sites.com/simplesaml (or your appropriate url for the site)
* Log in using the password as configured in the acquia_config.php
* Verify php installation at /simplesaml/module.php/core/frontpage_config.php
* Go to /simplesaml/module.php/saml/sp/metadata.php/default-sp?output=xhtml and copy the metadata
* Create a new SAML manager at https://spdb.stanford.edu/spconfigs/new
* Paste the above XML into the metadata xml
* Change the entityID to the exact same entityID as configured in the authsources.
* Wait up to 15 minutes.
* In simplesamlephp/metadata replace all contents with 
```
// Load file on acquia server.
if (file_exists('/mnt/gfs/[sitename].[env]/nobackup/saml/saml20-idp-remote.php')) {
  include_once '/mnt/gfs/[sitename].[env]/nobackup/saml/saml20-idp-remote.php';
}
```
* Include the following in the /mnt/gfs/[site].[environment]/nobackup/saml/saml20-idp-remote.php on Acquia Server
```
$metadata['https://idp.stanford.edu/'] = array(
  'name' => array(
    'en' => 'Stanford University WebLogin',
  ),
  'description'         => 'Stanford University WebLogin',
  'SingleSignOnService' => 'https://idp.stanford.edu/idp/profile/SAML2/Redirect/SSO',
  'certFingerprint'     => '{fingerprint}'
);
```
* go to the page /simplesaml/module.php/core/authenticate.php and test using the `default-sp` source
* verify you get a valid response with your information.

# Drupal
* Add and enable simplesamlphp_auth module
* Configure as desired on page /admin/config/people/simplesamlphp_auth
* Basic Settings:
    * Authentication source should be `default-sp`
    * Log in link is what the user will click on. like "Stanford Login"
    * Check "Register users"
* Local authentication:
    * Check "Allow authentication with local Drupal accounts"
    * Uncheck "Allow SAML users to set Drupal passwords"
* User info and syncing
    * Unique identifier should be `uid`
    * username can either be `uid` or `displayName`
    * Email should be `eduPersonPrincipalName`