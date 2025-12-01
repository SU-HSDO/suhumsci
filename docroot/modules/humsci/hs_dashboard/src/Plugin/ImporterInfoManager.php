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
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_default
   *   The cache backend to use.
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

    usort($instances, function ($a, $b) {
      return $a->getWeight() - $b->getWeight();
    });

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

    /** @var \Drupal\hs_dashboard\Plugin\ImporterInfoInterface $importer */
    foreach ($this->getImporterInstances() as $plugin_id => $importer) {
      $caption = $importer->getCaption();
      $rows = $importer->getTableRows();

      if (!$importer->showImporter()) {
        // Skip the importer if its visibility is set to not display.
        continue;
      }

      if (empty($rows)) {
        $no_data_caption = $importer->getNoDataCaption();
        $tables[] = [
          '#theme' => 'table',
          '#caption' => [
            '#markup' => $caption,
          ],
          '#rows' => [
            [
              [
                'data' => $no_data_caption,
                'colspan' => count($importer->getTableHeaders()),
                'class' => ['importers-no-data-message'],
              ],
            ],
          ],
        ];
      }
      else {
        $tables[] = [
          '#theme' => 'table',
          '#caption' => $caption,
          '#header' => $importer->getTableHeaders(),
          '#rows' => $rows,
        ];

        if (!empty($importer->getTableSuffix())) {
          $tables[count($tables) - 1]['#footer'] = [
            [
              [
                'data' => $importer->getTableSuffix(),
                'colspan' => count($importer->getTableHeaders()),
                'class' => ['importers-table-footer'],
              ],
            ],
          ];
        }
      }

    }

    // There's some caching issues.  If the underlying data in these tables
    // changes, you need to do a full cache clear in order to see the change.
    // The code below _should_ cover it, but it's being cached somewhere higher
    // in the stack. We'll create a ticket in ClickUp to investigate further.
    //
    // Collect cache tags for all migrations.
    $cache_tags = [
      'config:migrate_plus.migration_list',
      'config:migrate.migration_list',
    ];
    $migration_storage = \Drupal::entityTypeManager()->getStorage('migration');
    foreach ($migration_storage->loadMultiple() as $migration) {
      $cache_tags[] = 'config:' . $migration->getConfigDependencyName();
    }

    $this->cache->set('hs_dashboard_importer_info_tables', $tables, time() + 900, $cache_tags);
    $tables['#cache']['tags'] = $cache_tags;

    return $tables;

  }

}
