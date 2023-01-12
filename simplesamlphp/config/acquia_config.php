<?php

/**
 * @file
 * SimpleSamlPhp Acquia Configuration.
 *
 * This file was last modified on in July 2018.
 *
 * All custom changes below. Modify as needed.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;
use SimpleSAML\Logger;

if (file_exists(EnvironmentDetector::getAhFilesRoot() . '/secrets.settings.php')) {
  require EnvironmentDetector::getAhFilesRoot() . '/secrets.settings.php';
}

/**
 * Defines Acquia account specific options in $config keys.
 *
 *   - 'store.sql.name': Defines the Acquia Cloud database name which
 *     will store SAML session information.
 *   - 'store.type: Define the session storage service to use in each
 *     Acquia environment ("defualts to sql").
 */

// Set some security and other configs that are set above, however we
// overwrite them here to keep all changes in one area.
$config['technicalcontact_name'] = "Mike Decker";
$config['technicalcontact_email'] = "mike.decker@stanford.edu";

// Change these for your installation.
$config['secretsalt'] = getenv('SAML_SECRET_SALT');
$config['auth.adminpassword'] = getenv('SAML_ADMIN_PASS');

// do Acquia specific translations here
// Prevent Varnish from interfering with SimpleSAMLphp.
// SSL terminated at the ELB / balancer so we correctly set the SERVER_PORT
// and HTTPS for SimpleSAMLphp baseurl configuration.
$protocol = 'http://';
$port = '80';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
  $_SERVER['SERVER_PORT'] = 443;
  $_SERVER['HTTPS'] = 'true';
  $protocol = 'https://';
  $port = $_SERVER['SERVER_PORT'];
}
$config['baseurlpath'] = $protocol . $_SERVER['HTTP_HOST'] . ':' . $port . '/simplesaml/';
$config['trusted.url.domains'] = [$_SERVER['HTTP_HOST']];

// Setup basic file based logging.
$config['logging.handler'] = 'file';
// on Cloud Next, the preferred location is /shared/logs
// on Cloud Classic, the preferred location is the same directory as ACQUIA_HOSTING_DRUPAL_LOG
$config['loggingdir'] = (file_exists('/shared/logs/')) ? '/shared/logs/' : dirname(getenv('ACQUIA_HOSTING_DRUPAL_LOG'));
$config['logging.logfile'] = 'simplesamlphp-' . date('Ymd') . '.log';

// Retrieve database credentials from creds.json
$creds_json = file_get_contents('/var/www/site-php/' . $_ENV['AH_SITE_GROUP'] . '.' . $_ENV['AH_SITE_ENVIRONMENT'] . '/creds.json');
$creds = json_decode($creds_json, TRUE);

$database = $creds['databases'][$_ENV['AH_SITE_GROUP']];
// On Cloud Classic, the current active database host is determined by a DNS lookup
if (isset($database['db_cluster_id'])) {
  require_once "/usr/share/php/Net/DNS2_wrapper.php";
  try {
    $resolver = new Net_DNS2_Resolver([
      'nameservers' => [
        '127.0.0.1',
        'dns-master',
      ],
    ]);
    $response = $resolver->query("cluster-{$database['db_cluster_id']}.mysql", 'CNAME');
    $database['host'] = $response->answer[0]->cname;
  } catch (Net_DNS2_Exception $e) {
    Logger::warning('DNS entry not found');
  }
}
$config['store.type'] = 'sql';
$config['store.sql.dsn'] = sprintf('mysql:host=%s;port=%s;dbname=%s', $database['host'], $database['port'], $database['name']);
$config['store.sql.username'] = $database['user'];
$config['store.sql.password'] = $database['pass'];
$config['store.sql.prefix'] = 'simplesaml';
