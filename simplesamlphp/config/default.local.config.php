<?php

/**
 * @file
 * Include any changes to the generic config here.
 */

$root = dirname(dirname(dirname(__FILE__)));

$config['authproc.sp'] = array(
  10 => array(
    'class' => 'core:AttributeMap', 'removeurnprefix', 'oid2name',
  ),
  90 => 'core:LanguageAdaptor',
);

$config['enable.saml20-idp'] = TRUE;
$config['metadata.sources'][] = [
  'type' => 'flatfile',
  'directory' => dirname(dirname(dirname(__FILE__))) . '/keys/saml',
];

$config['store.type'] = 'sql';
$config['store.sql.prefix'] = 'simplesaml';

// Modify the following lines to match your database credentials.
$config['store.sql.dsn'] = 'mysql:host=localhost;dbname=[dbname]';
$config['store.sql.username'] = '[mysql username]';
$config['store.sql.password'] = '[mysql password]';
$config['trusted.url.domains'][] = "mysite.suhumsci.loc";