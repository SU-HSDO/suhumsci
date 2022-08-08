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
    $migration_groups = [
      'migrate_plus.migration_group.hs_capx',
      'migrate_plus.migration_group.hs_capx_publications',
    ];

    foreach ($names as $name) {
      if (substr($name, 0, 23) == 'migrate_plus.migration.') {
        $migration_group = 'migrate_plus.migration_group.';
        $migration_group .= $this->configFactory->getEditable($name)
          ->getOriginal('migration_group', FALSE);

        if (in_array($migration_group, $migration_groups)) {
          $urls = $this->configFactory->get($migration_group)
            ->get('shared_configuration.source.urls');
          $overrides[$name]['status'] = !empty($urls);
        }
      }
    }

    if (!array_intersect($names, $migration_groups)) {
      return $overrides;
    }

    $config = $this->configFactory->get('hs_capx.settings');
    $password = '';
    if ($key = Key::load($config->get('password') ?: '')) {
      $password = $key->getKeyValue();
    }

    if (in_array('migrate_plus.migration_group.hs_capx', $names)) {
      try {
        $urls = $this->getCapxUrls();
        $overrides['migrate_plus.migration_group.hs_capx'] = [
          'status' => !empty($urls),
          'shared_configuration' => [
            'source' => [
              'authentication' => [
                'client_id' => $config->get('username'),
                'client_secret' => $password,
                'plugin' => !empty($urls) && $password ? 'oauth2' : '',
              ],
              'orphan_action' => $config->get('orphan_action'),
              'urls' => $urls,
            ],
            'process' => $this->getFieldOverrides(),
          ],
        ];
      }
      catch (\Exception $e) {
        // Do nothing
      }
    }

    if (in_array('migrate_plus.migration_group.hs_capx_publications', $names)) {
      try {
        $urls = $this->getCapxUrls(TRUE);
        $overrides['migrate_plus.migration_group.hs_capx_publications'] = [
          'status' => !empty($urls),
          'shared_configuration' => [
            'source' => [
              'authentication' => [
                'client_id' => $config->get('username'),
                'client_secret' => $password,
                'plugin' => !empty($urls) && $password ? 'oauth2' : '',
              ],
              'orphan_action' => $config->get('orphan_action'),
              'urls' => $urls,
            ],
            'process' => $this->getFieldOverrides(),
          ],
        ];
      }
      catch (\Exception $e) {
        // Do nothing
      }
    }
    return $overrides;
  }

  /**
   * Get the appropriate CAPx urls.
   *
   * @return string[]
   *   List of CAPx Urls.
   */
  protected function getCapxUrls($publications = FALSE) {
    $urls = [];

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($this->importers as $importer) {
      if ($publications && $importer->importPublications()) {
        $urls = array_merge($urls, $importer->getCapxUrls());
      }
      if (!$publications && $importer->importProfiles()) {
        $urls = array_merge($urls, $importer->getCapxUrls());
      }
    }
    return array_filter(array_unique($urls));
  }

  /**
   * Get any field tagging overrides for all importers.
   *
   * @return array
   *   Keyed array of importer overrides.
   */
  protected function getFieldOverrides() {
    $processes = [];

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($this->importers as $importer) {
      foreach (array_keys($importer->getFieldTags()) as $field_name) {
        $processes[$field_name] = [
          [
            'plugin' => 'capx_tagging',
          ],
        ];
      }
    }
    return $processes;
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
