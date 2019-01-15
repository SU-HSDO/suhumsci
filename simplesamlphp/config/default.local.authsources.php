<?php

/**
 * @file
 * Include any necessary changes to the authsources config here.
 */

// This file should be copied into vendor/simplesamlphp/simplesamlphp/config.
$root = dirname(__FILE__, 5);

$config['default-sp']['entityID'] = 'https://mrc.stanford.edu';
$config['default-sp']['privatekey'] = "$root/keys/saml/saml.pem";
$config['default-sp']['certificate'] = "$root/keys/saml/saml.crt";
