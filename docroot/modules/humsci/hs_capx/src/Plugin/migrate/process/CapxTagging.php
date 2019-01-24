<?php

namespace Drupal\hs_capx\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CapX Specific process plugin to populate taxonomy term fields during import.
 *
 * @MigrateProcessPlugin(
 *   id = "capx_tagging",
 *   handle_multiples = TRUE
 * )
 */
class CapxTagging extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $destination = explode(Row::PROPERTY_SEPARATOR, $destination_property);
    $field_name = reset($destination);
    $url = $row->getSourceProperty('active_url');

    $importers = $this->entityTypeManager->getStorage('capx_importer')
      ->loadMultiple();
    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($importers as $importer) {
      if (in_array($url, $importer->getCapxUrls())) {
        return $importer->getFieldTags($field_name);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
