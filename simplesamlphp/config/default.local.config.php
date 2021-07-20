<?php

/**
 * @file
 * Include any changes to the generic config here.
 */

$config['store.type'] = 'sql';
$config['store.sql.prefix'] = 'simplesaml';
$config['store.sql.dsn'] = 'mysql:host=${drupal.db.host};dbname=${drupal.db.database}';
$config['store.sql.username'] = '${drupal.db.username}';
$config['store.sql.password'] = '${drupal.db.password}';
$config['certdir'] = '${repo.root}/keys/saml/cert';
$config['metadatadir'] = '${repo.root}/keys/saml/metadata';

