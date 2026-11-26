<?php

declare(strict_types=1);

use DrupalRector\Set\DrupalSetProvider;
use Rector\Config\RectorConfig;
use Rector\Symfony\Symfony73\Rector\Class_\GetFiltersToAsTwigFilterAttributeRector;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/docroot/modules/humsci',
    __DIR__ . '/docroot/profiles/humsci',
    __DIR__ . '/docroot/themes/humsci',
    ])
  ->withSkip([
    // Rector doesn't respect .gitignore, so theme dependencies get scanned.
    '*/node_modules/*',
    // Drupal registers Twig extensions through the twig.extension service tag
    // and doesn't support Twig's #[AsTwigFilter] attribute extensions.
    GetFiltersToAsTwigFilterAttributeRector::class,
  ])
  ->withSetProviders(DrupalSetProvider::class)
  ->withComposerBased(twig: TRUE, phpunit: TRUE, symfony: TRUE, drupal: TRUE);
