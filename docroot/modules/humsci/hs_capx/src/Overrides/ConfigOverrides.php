<?php

namespace Drupal\hs_capx\Overrides;

use Drupal\SwsDrush\Helpers\EnvironmentDetector;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\key\Entity\Key;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Configuration Overrides for the CapX importer.
 *
 * @package Drupal\hs_capx\Overrides
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {
  use LoggerAwareTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Array of available Capx Importers.
   *
   * NULL indicates the importers have not been loaded yet.
   *
   * @var \Drupal\hs_capx\Entity\CapxImporter[]|null
   */
  protected $importers;

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
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Override the CapX importer urls, add oauth credentials, and add field
   * tagging overrides to the importer.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
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
    try {
      if ($key = Key::load($config->get('password') ?: '')) {
        $password = $key->getKeyValue();
      }
    }
    catch (EncryptException $exception) {
      if (EnvironmentDetector::isLocalEnv()) {
        $this->getLogger()->notice('Encryption key not found for capx migrations. You will not be able to run these migrations without it.  Only needed if you are debugging these migrations.');
      }
      else {
        throw $exception;
      }
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
              'orphan_action' => $config->get('orphan_action') ?: 'forget',
              'urls' => $urls,
            ],
            'process' => $this->getFieldOverrides(),
          ],
        ];
      }
      catch (\Exception $e) {
        // Do nothing.
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
        // Do nothing.
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
    foreach ($this->getImporters() as $importer) {
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
    foreach ($this->getImporters() as $importer) {
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

  /**
   * Avoid circular dependency reference when using dependency injection.
   *
   * @return \Psr\Log\LoggerInterface
   */
  protected function getLogger(): LoggerInterface {
    if (!isset($this->logger)) {
      // @phpstan-ignore-next-line
      $this->logger = \Drupal::logger('capx');
    }
    return $this->logger;
  }

  /**
   * Get importer entities.
   *
   * @return \Drupal\hs_capx\Entity\CapxImporter[]
   *   Loaded CapX importer entities.
   */
  protected function getImporters(): array {
    if ($this->importers !== NULL) {
      return $this->importers;
    }

    $this->importers = [];

    if ($this->entityTypeManager->hasDefinition('capx_importer')) {
      /** @var \Drupal\hs_capx\Entity\CapxImporter[] $importers */
      $importers = $this->entityTypeManager->getStorage('capx_importer')
        ->loadMultiple();
      $this->importers = $importers;
    }

    return $this->importers;
  }

}
