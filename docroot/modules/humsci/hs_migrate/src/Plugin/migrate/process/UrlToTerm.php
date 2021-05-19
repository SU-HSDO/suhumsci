<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration process plugin to get terms that are associated to the give value.
 *
 * @MigrateProcessPlugin(
 *   id = "url_to_term"
 * )
 */
class UrlToTerm extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Core entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritDoc}
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
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $importer_storage = $this->entityTypeManager->getStorage('importers');
    $eck_ids = $importer_storage->getQuery()
      ->condition('field_url', $value)
      ->accessCheck(FALSE)
      ->execute();

    if ($eck_ids) {
      $eck = $importer_storage->load(reset($eck_ids));
      return $eck->get('field_terms')->getValue();
    }
    return NULL;
  }

}
