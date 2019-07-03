<?php

namespace Drupal\hs_capx\Overrides;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Array of available Capx Importers.
   *
   * @var \Drupal\hs_capx\Entity\CapxImporter[]
   */
  protected $importers = [];

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    if ($entity_type_manager->hasDefinition('capx_importer')) {
      $this->importers = $entity_type_manager->getStorage('capx_importer')
        ->loadMultiple();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Override the CapX importer urls, add oauth credentials, and add field
   * tagging overrides to the importer.
   */
  public function loadOverrides($names) {
    $overrides = [];
    $configs_to_override = $this->overrideTheseConfigs($names);
    if (empty($configs_to_override)) {
      return [];
    }

    $config = $this->configFactory->get('hs_capx.settings');
    $password = '';
    if ($key = Key::load($config->get('password') ?: '')) {
      $password = $key->getKeyValue();
    }

    // Set the migration urls and client credentials from the user entered
    // data.
    foreach ($configs_to_override as $config_name => $needs_fields) {
      $overrides[$config_name] = [
        'source' => [
          'authentication' => [
            'client_id' => $config->get('username'),
            'client_secret' => $password,
            'plugin' => $password ? 'oauth2' : '',
          ],
          'urls' => $this->getCapxUrls(),
        ],
      ];

      if ($needs_fields) {
        // Add tagging for profiles.
        $overrides[$config_name] += $this->getFieldOverrides();
      }
    }

    return $overrides;
  }

  /**
   * Get the migration config names that need to be overridden.
   *
   * @param array $names
   *   Array of config names.
   *
   * @return array
   *   Keyed array of configs names with the values if the config is for nodes.
   */
  protected function overrideTheseConfigs(array $names = []) {
    $configs_to_override = [];
    foreach ($names as $name) {
      if (strpos($name, 'migrate_plus.migration.') !== FALSE) {
        $migration_group = $this->configFactory->getEditable($name)
          ->getOriginal('migration_group', FALSE);

        if ($migration_group == 'hs_capx') {
          $migrate_destination = $this->configFactory->getEditable($name)
            ->getOriginal('destination.plugin', FALSE);

          $configs_to_override[$name] = $migrate_destination == 'entity_reference_revisions:node';
        }
      }
    }
    return $configs_to_override;
  }

  /**
   * Get the appropriate CAPx urls.
   *
   * @return array
   *   List of CAPx Urls.
   */
  protected function getCapxUrls() {
    $urls = [];

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($this->importers as $importer) {
      $urls = array_merge($urls, $importer->getCapxUrls());
    }
    $urls = array_filter(array_unique($urls));

    // If no workgroups or organizations are configured, use a dummy url with no
    // data to prevent unwanted error messages.
    return $urls ?: ['http://ip.jsontest.com'];
  }

  /**
   * Get any field tagging overrides for all importers.
   *
   * @return array
   *   Keyed array of importer overrides.
   */
  protected function getFieldOverrides() {
    $overrides = [];

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($this->importers as $importer) {
      foreach (array_keys($importer->getFieldTags()) as $field_name) {
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
    $cacheable_data = new CacheableMetadata();
    if ($name == 'migrate_plus.migration.hs_capx') {
      $cacheable_data->setCacheTags(['hs_capx_config']);
    }
    return $cacheable_data;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
