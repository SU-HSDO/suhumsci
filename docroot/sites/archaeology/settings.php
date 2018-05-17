<?php

require DRUPAL_ROOT . "/sites/default/settings.php";

if (file_exists('/var/www/site-php')) {
  require '/var/www/site-php/swshumsci/archaeology-settings.inc';
}