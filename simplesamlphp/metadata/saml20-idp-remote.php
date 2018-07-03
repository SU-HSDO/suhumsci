<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */


// Load file on acquia server.
if (file_exists('/mnt/gfs/swshumsci.prod/nobackup/apikeys/saml/saml20-idp-remote.php')) {
  include_once '/mnt/gfs/swshumsci.prod/nobackup/apikeys/saml/saml20-idp-remote.php';
}
