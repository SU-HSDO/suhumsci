<?php

namespace Drupal\hs_dashboard\Plugin\views\relationship;

use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Annotation\ViewsRelationship;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a relationship to join nodes from editoria11y_results.
 *
 * @ViewsRelationship("editoria11y_results_node")
 */
class NodeFromEditoria11yResults extends RelationshipPluginBase {

  /**
   * The join plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * Constructs a NodeFromEditoria11yResults object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The join plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $def = [
      'left_table' => $this->tableAlias,
      'left_field' => 'entity_id',
      'table' => 'node_field_data',
      'field' => 'nid',
      'extra' => [
        [
          'left_field' => 'route_name',
          'value' => 'entity.node.canonical',
          'operator' => '=',
        ],
      ],
    ];
    $join = $this->joinManager->createInstance('standard', $def);
    $alias = $this->query->addRelationship($this->options['id'], $join, 'node_field_data');
    $this->alias = $alias;
  }

}
