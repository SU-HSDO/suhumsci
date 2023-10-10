<?php

namespace Drupal\hs_migrate\Overrides;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Migration configuration overrides from config pages values.
 */
class MigrationOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * Core entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MigrationOverrides constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages_loader
   *   Config pages loader service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Core entity type manager service.
   */
  public function __construct(ConfigPagesLoaderServiceInterface $config_pages_loader, EntityTypeManagerInterface $entity_type_manager) {
    $this->configPagesLoader = $config_pages_loader;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('migrate_plus.migration.hs_news_rss', $names) && $this->entityTypeManager->hasDefinition('hs_entity')) {

      $urls = [];
      $entity_ids = $this->configPagesLoader->getValue('news_rss', 'field_news_rss', [], 'target_id');

      if ($entity_ids) {
        $news_entities = $this->entityTypeManager->getStorage('hs_entity')
          ->loadMultiple($entity_ids);
        foreach ($news_entities as $entity) {
          $urls[] = $entity->get('field_url')->getString();
        }
      }
      $overrides['migrate_plus.migration.hs_news_rss']['status'] = !empty($urls);
      $overrides['migrate_plus.migration.hs_news_rss']['source']['urls'] = $urls;
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
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
  public function getCacheSuffix() {
    return 'MigrationImporterOverrides';
  }

}
