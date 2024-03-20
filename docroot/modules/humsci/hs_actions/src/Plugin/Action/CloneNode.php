<?php

namespace Drupal\hs_actions\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\hs_actions\Plugin\FieldCloneManagerInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clones a node.
 *
 * @Action(
 *   id = "node_clone_action",
 *   label = @Translation("Clone selected content"),
 *   type = "node"
 * )
 */
class CloneNode extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Field clone plugin manager service.
   *
   * @var \Drupal\hs_actions\Plugin\FieldCloneManagerInterface
   */
  protected $fieldCloneManager;

  /**
   * Array of field clone plugins.
   *
   * @var \Drupal\hs_actions\Plugin\Action\FieldClone\FieldCloneInterface[]
   */
  protected $fieldClonePlugins;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.hs_actions_field_clone')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, FieldCloneManagerInterface $field_clone_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldCloneManager = $field_clone_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'clone_count' => 1,
      'field_clone' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $values = range(1, 10);
    $form['clone_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Clone how many times'),
      '#options' => array_combine($values, $values),
    ];

    foreach ($this->context['list'] as $item) {
      $node_ids[] = $item[0];
    }

    // Load all nodes that are being cloned.
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($node_ids);

    $form['field_clone'] = [
      '#type' => 'details',
      '#title' => $this->t('Adjust Cloned Field Values'),
      '#tree' => TRUE,
    ];

    // Add field clone fields to the form.
    foreach ($nodes as $node) {
      $this->buildFieldCloneForm($form, $form_state, $node);
    }

    // If no plugins add to the form, remove the fieldset.
    if (empty(Element::children($form['field_clone']))) {
      unset($form['field_clone']);
    }

    return $form;
  }

  /**
   * Build the field clone form for the provided entity.
   *
   * @param array $form
   *   Complete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $node
   *   Entity to be cloned.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildFieldCloneForm(array &$form, FormStateInterface $form_state, FieldableEntityInterface $node) {
    $field_clone_plugins = $this->getFieldClonePlugins();

    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field) {
      foreach ($field_clone_plugins as $plugin) {

        $plugin_definition = $plugin->getPluginDefinition();
        if (in_array($field->getType(), $plugin_definition['fieldTypes'])) {

          $form['field_clone'][$plugin_definition['id']][$field->getName()] = [
            '#type' => 'details',
            '#title' => $field->getLabel(),
            '#description' => $plugin_definition['description'] ?? '',
            '#open' => TRUE,
          ];

          $form['field_clone'][$plugin_definition['id']][$field->getName()] += $plugin->buildConfigurationForm($form, $form_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach ($this->getFieldClonePlugins() as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach ($this->getFieldClonePlugins() as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
    $this->configuration['clone_count'] = $form_state->getValue('clone_count');
    $this->configuration['field_clone'] = $form_state->getValue('field_clone', []);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->access('create', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!isset($this->configuration['clone_count'])) {
      $this->configuration['clone_count'] = 1;
    }
    for ($i = 0; $i < $this->configuration['clone_count']; $i++) {
      $duplicate_node = $this->duplicateEntity($entity);
      $duplicate_node->save();
    }
  }

  /**
   * Recursively clone an entity and any dependent entities in reference fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to clone.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Cloned entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function duplicateEntity(ContentEntityInterface $entity) {
    $duplicate_entity = $entity->createDuplicate();

    // Loop through paragraph and eck fields to clone those entities.
    foreach ($this->getReferenceFields($entity->getEntityTypeId(), $entity->bundle()) as $field) {
      foreach ($duplicate_entity->{$field->getName()} as $value) {
        $value->entity = $this->duplicateEntity($value->entity);
      }
    }

    $field_plugins = $this->getFieldClonePlugins();

    foreach ($this->configuration['field_clone'] as $plugin_id => $fields) {
      foreach ($fields as $field_name => $field_changes) {
        $field_plugins[$plugin_id]->alterFieldValue($entity, $duplicate_entity, $field_name, $field_changes);
      }
    }

    return $duplicate_entity;
  }

  /**
   * Get all the field clone plugins available.
   *
   * @return \Drupal\hs_actions\Plugin\Action\FieldClone\FieldCloneInterface[]
   *   Keyed array of plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getFieldClonePlugins() {
    if (empty($this->fieldClonePlugins)) {
      foreach ($this->fieldCloneManager->getDefinitions() as $plugin_definition) {
        $this->fieldClonePlugins[$plugin_definition['id']] = $this->fieldCloneManager->createInstance($plugin_definition['id']);
      }
    }
    return $this->fieldClonePlugins;
  }

  /**
   * Get fields that need to have their referenced entities cloned.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\field\Entity\FieldConfig[]
   *   Array of fields that need cloned values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReferenceFields($entity_type_id, $bundle) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    if ($this->entityTypeManager->hasDefinition('eck_entity_type')) {
      $eck_types = $this->entityTypeManager->getStorage('eck_entity_type')
        ->loadMultiple();
      $clone_target_types = array_keys($eck_types);
    }

    $clone_target_types[] = 'paragraph';
    $clone_target_types[] = 'hs_entity';

    // Filter out fields that we dont care about. We only need entity reference
    // fields that are not base fields. Also we only want entity reference
    // fields that target specific entity types as defined above that require
    // cloning..
    $reference_fields = array_filter($fields, function ($field) use ($clone_target_types) {
      $target_entity_id = $field->getFieldStorageDefinition()
        ->getSetting('target_type');
      $types = ['entity_reference', 'entity_reference_revisions'];

      return $field instanceof FieldConfig && in_array($field->getType(), $types) && in_array($target_entity_id, $clone_target_types);
    });

    return $reference_fields;
  }

}
