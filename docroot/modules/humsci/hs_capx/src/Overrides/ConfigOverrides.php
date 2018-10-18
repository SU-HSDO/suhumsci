<?php

namespace Drupal\hs_capx\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

use Drupal\hs_capx\Capx;
use Drupal\key\Entity\Key;

/**
 * Class ConfigOverrides.
 *
 * @package Drupal\hs_capx\Overrides
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Override the path to the key for the encryption profile.
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('migrate_plus.migration.hs_capx', $names) || in_array('migrate_plus.migration.hs_capx_images', $names)) {

      $config = $this->configFactory->get('hs_capx.settings');
      $password = '';
      if ($key = Key::load($config->get('password') ?: '')) {
        $password = $key->getKeyValue();
      }

      $overrides['migrate_plus.migration.hs_capx'] = [
        'source' => [
          'authentication' => [
            'client_id' => $config->get('username'),
            'client_secret' => $password,
          ],
          'urls' => $this->getCapxUrls(),
        ],
        'status' => !empty($this->getCapxUrls()),
      ];
      $overrides['migrate_plus.migration.hs_capx_images'] = $overrides['migrate_plus.migration.hs_capx'];
    }
    return $overrides;
  }

  /**
   * Get the appropriate CAPx urls.
   *
   * @return array
   *   List of CAPx Urls.
   */
  protected function getCapxUrls() {
    $urls = [];
    $config = $this->configFactory->get('hs_capx.settings');
    if ($organizations = $config->get('organizations')) {
      $urls[] = Capx::getOrganizationUrl($organizations, $config->get('child_organizations'));
    }
    if ($workgroups = $config->get('workgroups')) {
      $urls[] = Capx::getWorkgroupUrl($workgroups);
    }
   return $urls;
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
