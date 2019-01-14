<?php

/**
 * @file
 * Include any changes to the generic config here.
 */

// This file should be copied into vendor/simplesamlphp/simplesamlphp/config.
$root = dirname(__FILE__, 5);

$config['authproc.sp'] = [
  10 => [
    'class' => 'core:AttributeMap',
    'removeurnprefix',
    'oid2name',
  ],
  90 => 'core:LanguageAdaptor',
];

$config['enable.saml20-idp'] = TRUE;
$config['metadata.sources'][] = [
  'type' => 'flatfile',
  'directory' => "$root/keys/saml",
];

$config['store.type'] = 'sql';
$config['store.sql.prefix'] = 'simplesaml';

// Modify the following lines to match your database credentials.
$config['store.sql.dsn'] = 'mysql:host=localhost;dbname=[dbname]';
$config['store.sql.username'] = '[mysql username]';
$config['store.sql.password'] = '[mysql password]';
$config['trusted.url.domains'][] = "mysite.suhumsci.loc";
