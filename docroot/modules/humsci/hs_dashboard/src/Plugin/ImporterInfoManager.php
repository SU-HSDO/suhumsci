<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\stanford_migrate\EventSubscriber\EventsSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class to handle Import information for block tables.
 */
class ImporterInfoManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
   *   The widget manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
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
  }

  public function getImporterInstances(): array {
  $instances = [];

  foreach ($this->getDefinitions() as $plugin_id => $definition) {
    $instances[$plugin_id] = $this->createInstance($plugin_id);
  }

  return $instances;
}

  public function generateTables(): array {
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

    return $tables; // Return an array of table render arrays
  }
}
