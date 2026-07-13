<?php

declare(strict_types=1);

use DrupalRector\Set\DrupalSetProvider;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/docroot/modules/humsci',
    __DIR__ . '/docroot/themes/humsci',
    __DIR__ . '/docroot/profiles/humsci',
    ])
  ->withSetProviders(DrupalSetProvider::class)
  ->withComposerBased(twig: TRUE, phpunit: TRUE, symfony: TRUE, drupal: TRUE);
