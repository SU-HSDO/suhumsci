<?php

namespace Drupal\hs_courses_importer\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Example configuration override.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Current request stack.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequestStack;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequestStack = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   *
   * Override the course importer url to point to the local url.
   */
  public function loadOverrides($names) {
    $host = $this->currentRequestStack->getSchemeAndHttpHost();
    $overrides = [];
    // Change the url of the course importer to point to local path.
    // This helps when changing environments or pulling to local.
    if (in_array('migrate_plus.migration.hs_courses', $names)) {
      $overrides['migrate_plus.migration.hs_courses'] = [
        'source' => [
          'urls' => $host . '/api/hs_courses',
        ],
      ];
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'ConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
