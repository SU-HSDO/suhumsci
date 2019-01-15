<?php

namespace Drupal\hs_capx\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConfigOverrides constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Override the path to the key for the encryption profile.
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (!(in_array('migrate_plus.migration.hs_capx', $names) || in_array('migrate_plus.migration.hs_capx_images', $names))) {
      return [];
    }

    $config = $this->configFactory->get('hs_capx.settings');
    $password = '';
    if ($key = Key::load($config->get('password') ?: '')) {
      $password = $key->getKeyValue();
    }

    // Set the migration urls and client credentials from the user entered
    // data.
    $overrides['migrate_plus.migration.hs_capx'] = [
      'source' => [
        'authentication' => [
          'client_id' => $config->get('username'),
          'client_secret' => $password,
          'plugin' => $password ? 'oauth2' : '',
        ],
        'urls' => $this->getCapxUrls(),
      ],
    ];

    // Image importer will have the same overrides.
    $overrides['migrate_plus.migration.hs_capx_images'] = $overrides['migrate_plus.migration.hs_capx'];
    // Add tagging for profiles.
    $overrides['migrate_plus.migration.hs_capx'] += $this->getFieldOverrides();
    return $overrides;
  }

  /**
   * Get the appropriate CAPx urls.
   *
   * @return array
   *   List of CAPx Urls.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getCapxUrls() {
    $urls = [];
    $importers = $this->entityTypeManager->getStorage('capx_importer')
      ->loadMultiple();
    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($importers as $importer) {
      $urls = array_merge($urls, $importer->getCapxUrls());
    }
    $urls = array_filter(array_unique($urls));

    // If no workgroups or organizations are configured, use a dummy url with no
    // data to prevent unwanted error messages.
    return $urls ?: ['http://ip.jsontest.com'];
  }

  protected function getFieldOverrides() {
    $overrides = [];
    $importers = $this->entityTypeManager->getStorage('capx_importer')
      ->loadMultiple();
    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($importers as $importer) {
      foreach ($importer->getFieldTags() as $field_name => $tags) {
        $overrides['process'][$field_name] = [
          [
            'plugin' => 'capx_tagging',
          ],
        ];
      }
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
