<?php

namespace Drupal\hs_migrate\Plugin\views\field;

use Drupal\stanford_migrate\StanfordMigrateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_migration")
 */
class EntityMigrationField extends FieldPluginBase {

  /**
   * Stanford migrate service.
   *
   * @var \Drupal\stanford_migrate\StanfordMigrateInterface
   */
  protected $stanfordMigrate;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stanford_migrate')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StanfordMigrateInterface $stanford_migrate) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stanfordMigrate = $stanford_migrate;
  }

  /**
   * {@inheritDoc}
   */
  public function query() {
    // Leave empty to avoid queries on a non-existent table/field.
  }

  /**
   * {@inheritDoc}
   */
  public function render(ResultRow $values) {
    try {
      $migration = $this->stanfordMigrate->getNodesMigration($values->_entity);
      if ($migration) {
        return ['#markup' => $migration->label()];
      }
    } catch (\Exception $e) {
      // Nothing to do.
    }
    return NULL;
  }

}
