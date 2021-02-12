<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "field_default_value"
 * )
 */
class FieldDefaultValue extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Type Manager Service.
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
    if (!empty($value)) {
      return $value;
    }
    $entity_type = $this->configuration['entity_type'];
    $bundle = $this->configuration['bundle'];
    $field_name = $this->configuration['field'];
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $this->entityTypeManager->getStorage('field_config')
      ->load("$entity_type.$bundle.$field_name");

    if (!$field) {
      return NULL;
    }

    try {
      $entity = $this->getEmptyEntity($entity_type, $bundle);
      $value = $field->getDefaultValue($entity);
      return $value[0] ?? $value;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get an empty entity that we can use for the field default value.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param string $bundle
   *   Entity type bundle.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Empty stubbed out entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEmptyEntity($entity_type, $bundle) {
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)
      ->getKey('bundle');
    return $this->entityTypeManager->getStorage($entity_type)
      ->create([$bundle_key => $bundle]);
  }

}
