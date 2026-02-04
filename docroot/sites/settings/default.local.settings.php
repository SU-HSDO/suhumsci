<?php

if (file_exists(DRUPAL_ROOT . '/../keys/secrets.settings.php')) {
  require DRUPAL_ROOT . '/../keys/secrets.settings.php';
}

$config['devel.settings']['devel_dumper'] = 'var_dumper';
// Prevent errors from showing in the UI for prod & qa environments.
error_reporting(E_ALL & ~E_DEPRECATED);

/**
 * SAML configuration
 */
if (file_exists(DRUPAL_ROOT . '/../keys/saml/cert/saml.crt')) {
  $config['samlauth.authentication']['sp_x509_certificate'] = 'file:' . DRUPAL_ROOT . '/../keys/saml/cert/saml.crt';
  $config['samlauth.authentication']['sp_private_key'] = 'file:' . DRUPAL_ROOT . '/../keys/saml/cert/saml.pem';
  $config['samlauth.authentication']['idp_certs'] = [
    'file:' . DRUPAL_ROOT . '/../keys/saml/cert/signing.crt',
  ];
  $config['stanford_samlauth.settings']['role_mapping']['workgroup_api'] = [
    'cert' => DRUPAL_ROOT . '/../keys/saml/workgroup_api.cert',
    'key' => DRUPAL_ROOT . '/../keys/saml/workgroup_api.key',
  ];
}

// Saml login doesn't work on gitpod or tugboat, don't set config values.
if (getenv('GITPOD_WORKSPACE_URL') || getenv('TUGBOAT_REPO')) {
  unset($config['samlauth.authentication'], $config['stanford_samlauth.settings']);
}

if (file_exists(DRUPAL_ROOT . '/../keys.secrets.settings.php')) {
  require DRUPAL_ROOT . '/../keys/secrets.settings.php';
}
