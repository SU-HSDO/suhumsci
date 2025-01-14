<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

$config['samlauth.authentication']['sp_x509_certificate'] = 'file:' . DRUPAL_ROOT . '/../keys/saml/cert/saml.crt';
$config['samlauth.authentication']['sp_private_key'] = 'file:' . DRUPAL_ROOT . '/../keys/saml/cert/saml.pem';
$config['samlauth.authentication']['idp_certs'] = [
  'file:' . DRUPAL_ROOT . '/../keys/saml/cert/signing.crt',
];
$config['stanford_samlauth.settings']['role_mapping']['workgroup_api'] = [
  'cert' => DRUPAL_ROOT . '/../keys/saml/workgroup_api.cert',
  'key' => DRUPAL_ROOT . '/../keys/saml/workgroup_api.key',
];