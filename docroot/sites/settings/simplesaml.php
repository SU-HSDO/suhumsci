<?php

use Acquia\Blt\Robo\Common\EnvironmentDetector;

// SimpleSAMLphp configuration
// Set the workgroup api cert paths.
$config['stanford_ssp.settings'] = [
  'workgroup_api_cert' => DRUPAL_ROOT . "/../keys/saml/workgroup_api.cert",
  'workgroup_api_key' => DRUPAL_ROOT . "/../keys/saml/workgroup_api.key",
];

if (EnvironmentDetector::isAhEnv()) {
  $group = EnvironmentDetector::getAhGroup();
  $environment = EnvironmentDetector::getAhEnv();

  // SimpleSAMLphp configuration
  // Set the workgroup api cert paths.
  $config['stanford_ssp.settings'] = [
    'workgroup_api_cert' => "/mnt/gfs/$group.$environment/nobackup/apikeys/saml/workgroup_api.cert",
    'workgroup_api_key' => "/mnt/gfs/$group.$environment/nobackup/apikeys/saml/workgroup_api.key",
  ];
}

$config['simplesamlphp_auth.settings'] = [
  'langcode' => 'en',
  'default_langcode' => 'en',
  'mail_attr' => 'mail',
  'unique_id' => 'uid',
  'user_name' => 'displayName',
  'auth_source' => 'default-sp',
  'login_link_display_name' => 'Stanford Login',
  'header_no_cache' => TRUE,
  'user_register_original' => 'visitors',
  'register_users' => TRUE,
  'autoenablesaml' => TRUE,
  'debug' => FALSE,
  'secure' => FALSE,
  'httponly' => FALSE,
  'role' => [
    //    'population' => 'administrator:eduPersonEntitlement,=,hsdo:web|administrator:eduPersonEntitlement,=,itservices:webservices',
    //    'eval_every_time' => 2,
  ],
  'allow' => [
    'set_drupal_pwd' => FALSE,
    'default_login' => TRUE,
  ],
  'sync' => [
    'mail' => TRUE,
    'user_name' => TRUE,
  ],
];
