<?php

/**
 * @file
 * Include any necessary changes to the authsources config here.
 */

$root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

$config['default-sp']['entityID'] = 'https://mrc.stanford.edu';
$config['default-sp']['privatekey'] = "$root/keys/saml/saml.pem";
$config['default-sp']['certificate'] = "$root/keys/saml/saml.crt";
