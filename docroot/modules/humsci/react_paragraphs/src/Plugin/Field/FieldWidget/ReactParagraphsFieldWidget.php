<?php

namespace Drupal\react_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Plugin\EditorManager;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'react_paragraphs' widget.
 *
 * @FieldWidget(
 *   id = "react_paragraphs",
 *   label = @Translation("React Paragraphs"),
 *   field_types = {
 *     "react_paragraphs"
 *   },
 *   multiple_values = true
 * )
 */
class ReactParagraphsFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Reference selection manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * Entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Editor Plugin Manager service.
   *
   * @var \Drupal\editor\Plugin\EditorManager
   */
  protected $editorManager;

  /**
   * Keyed array of item ids.
   *
   * @var array
   */
  protected $paragraphIds;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.editor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, SelectionPluginManagerInterface $selection_manager, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, EditorManager $editor_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->editorManager = $editor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element_id = Html::getUniqueId(str_replace('.', '-', $this->fieldDefinition->id()));

    $attachments = $this->editorManager->getAttachments(array_keys(filter_formats()));
    $attachments['library'][] = 'react_paragraphs/field_widget';
    $attachments['library'][] = 'filter/drupal.filter.admin';

    $item_value = array_filter($items->getValue());
    foreach ($item_value as &$item) {
      $item['settings'] = json_decode($item['settings'], TRUE);
    }

    $attachments['drupalSettings']['reactParagraphs'][] = [
      'fieldId' => $element_id,
      'entityId' => $form_state->getBuildInfo()['callback_object']->getEntity()
        ->id(),
      'available_items' => $this->getAllowedTypes($this->fieldDefinition),
      'existing_items' => $item_value,
      'fieldName' => $this->fieldDefinition->getName(),
    ];

    $elements['value'] = $element;
    $elements['value'] += [
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#suffix' => "<div id='$element_id'></div>",
      '#attached' => $attachments,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $react_data = json_decode(urldecode($values['value']), TRUE);
    $return_data = [];
    if (!isset($react_data['rowOrder'])) {
      return [];
    }

    foreach ($react_data['rowOrder'] as $row_index => $row_id) {

      foreach ($react_data['rows'][$row_id]['items'] as $item_index => $item_id) {
        $entity = $this->getEntity($item_id, $react_data['items'][$item_id]);
        $react_data['items'][$item_id]['settings']['index'] = $item_index;
        $react_data['items'][$item_id]['settings']['row'] = $row_index;

        $return_data[] = [
          'entity' => $entity,
          'target_id' => $entity->id(),
          'target_revision_id' => $entity->getRevisionId(),
          'settings' => json_encode($react_data['items'][$item_id]['settings']),
        ];
      }
    }

    return $return_data;
  }

  /**
   * Get the existing paragraph entity if it exists, or create a new one.
   *
   * @param string $item_id
   *   Unique id from the react widget.
   * @param array $item_data
   *   Entity field data.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   Paragraph entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getEntity($item_id, array $item_data) {
    if (isset($this->paragraphIds[$item_id])) {
      return $this->paragraphIds[$item_id];
    }
    if (!empty($item_data['target_id'])) {
      $entity = Paragraph::load($item_data['target_id']);

      foreach ($item_data['entity'] as $field_name => $field_value) {
        if (array_filter($field_value) && strpos($field_name, 'field_') !== FALSE) {
          $entity->set($field_name, $field_value);
        }
      }
    }
    else {
      $entity = Paragraph::create($item_data['entity']);
      $entity->save();
    }

    $this->paragraphIds[$item_id] = $entity;
    return $this->paragraphIds[$item_id];
  }

  /**
   * Returns the sorted allowed types for a entity reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface|null $field_definition
   *   (optional) The field definition forwhich the allowed types should be
   *   returned, defaults to the current field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name w/ properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllowedTypes(FieldDefinitionInterface $field_definition = NULL) {
    $return_bundles = [];
    $handler = $this->selectionManager->getSelectionHandler($field_definition ?: $this->fieldDefinition);
    if ($handler instanceof ParagraphSelection) {
      $return_bundles = $handler->getSortedAllowedTypes();
    }
    $bundle_entities = $this->entityTypeManager->getStorage('paragraphs_type')
      ->loadMultiple(array_keys($return_bundles));

    /** @var \Drupal\paragraphs\ParagraphsTypeInterface $paragraph_type */
    foreach ($bundle_entities as $id => $paragraph_type) {
      $return_bundles[$id]['icon'] = NULL;
      if ($icon_uuid = $paragraph_type->get('icon_uuid')) {
        $file = $this->entityTypeManager->getStorage('file')
          ->loadByProperties(['uuid' => $icon_uuid]);

        if (!empty($file)) {
          /** @var \Drupal\file\Entity\File $file */
          $file = is_array($file) ? reset($file) : $file;
          $return_bundles[$id]['icon'] = file_create_url($file->getFileUri());
        }
      }
    }
    return $return_bundles;
  }

}
