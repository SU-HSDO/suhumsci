<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to handle Import information for block tables.
 */
class ImporterInfoManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * Constructs a new ImporterInfoManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    CacheBackendInterface $cache_default,
  ) {
    parent::__construct(
      'Plugin/ImporterInfo',
      $namespaces,
      $module_handler,
      'Drupal\hs_dashboard\Plugin\ImporterInfoInterface',
      'Drupal\hs_dashboard\Annotation\ImporterInfo'
    );
    $this->alterInfo('hs_dashboard_importer_info_info');
    $this->setCacheBackend($cache_backend, 'hs_dashboard_importer_info_plugins');
    $this->cache = $cache_default;
  }

  /**
   * Gets all Importer instances.
   */
  public function getImporterInstances(): array {
    $instances = [];

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id);
    }

    return $instances;
  }

  /**
   * Generates tables for all Importers.
   */
  public function generateTables(): array {
    if ($cache = $this->cache->get('hs_dashboard_importer_info_tables')) {
      return $cache->data;
    }

    $tables = [];

    foreach ($this->getImporterInstances() as $plugin_id => $importer) {
      $caption = $importer->getCaption();
      $rows = $importer->getTableRows();

      if (empty($rows)) {
        $no_data_caption = $importer->getNoDataCaption();
        $tables[] = [
          '#theme' => 'table',
          '#caption' => [
            '#markup' => "$caption<br>$no_data_caption",
          ],

        ];
      }
      else {
        $tables[] = [
          '#theme' => 'table',
          '#caption' => $caption,
          '#header' => $importer->getTableHeaders(),
          '#rows' => $rows,
          '#suffix' => $importer->getTableSuffix(),
        ];
      }

    }

    $this->cache->set('hs_dashboard_importer_info_tables', $tables, time() + 300);
    return $tables;

  }

}
