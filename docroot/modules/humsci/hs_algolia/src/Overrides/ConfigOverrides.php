<?php

namespace Drupal\hs_algolia\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Supplies per-site Algolia credentials and index name at runtime.
 *
 * The Search API server and index ship with empty credentials and no index
 * name. Each site fills them in on the Algolia settings form, which stores the
 * values in hs_algolia.settings and a key entity. Applying them as config
 * overrides keeps them out of the entities themselves, so a config export never
 * sees them.
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  const SERVER = 'search_api.server.hs_algolia';

  const INDEX = 'search_api.index.hs_algolia';

  const SETTINGS = 'hs_algolia.settings';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $wants_server = in_array(self::SERVER, $names, TRUE);
    $wants_index = in_array(self::INDEX, $names, TRUE);
    if (!$wants_server && !$wants_index) {
      return $overrides;
    }

    $settings = $this->configFactory->get(self::SETTINGS);

    if ($wants_server) {
      $application_id = (string) $settings->get('application_id');
      $api_key = $this->getKeyValue($settings->get('api_key'));
      if ($application_id !== '' && $api_key !== '') {
        $overrides[self::SERVER]['backend_config'] = [
          'application_id' => $application_id,
          'api_key' => $api_key,
        ];
      }
    }

    if ($wants_index) {
      $index_name = (string) $settings->get('index_name');
      if ($index_name !== '') {
        $overrides[self::INDEX]['options']['algolia_index_name'] = $index_name;
      }
    }

    return $overrides;
  }

  /**
   * Read the value of a key entity.
   *
   * @param string|null $key_id
   *   ID of the key entity, or NULL when none is configured.
   *
   * @return string
   *   The key value, or an empty string when the key is missing or empty.
   */
  protected function getKeyValue(?string $key_id): string {
    if (!$key_id || !$this->entityTypeManager->hasDefinition('key')) {
      return '';
    }

    /** @var \Drupal\key\KeyInterface|null $key */
    $key = $this->entityTypeManager->getStorage('key')->load($key_id);
    return $key ? (string) $key->getKeyValue() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'hs_algolia';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    if (in_array($name, [self::SERVER, self::INDEX], TRUE)) {
      $metadata->addCacheTags(['config:' . self::SETTINGS]);
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
