<?php

namespace Drupal\react_paragraphs\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection;
use Drupal\paragraphs\Plugin\Field\FieldWidget\ParagraphsWidget;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.entity_reference_selection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, SelectionPluginManagerInterface $selection_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element_id = Html::getUniqueId(str_replace('.', '-', $this->fieldDefinition->id()));
    $element['value'] = $element + [
        '#type' => 'hidden',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
        '#maxlength' => $this->getFieldSetting('max_length'),
        '#suffix' => "<div id='$element_id'></div>",
        '#attached' => [
          'library' => ['react_paragraphs/field_widget'],
          'drupalSettings' => [
            'reactParagraphs' => [
              [
                'fieldId' => $element_id,
                'entityId' => $form_state->getBuildInfo()['callback_object']->getEntity()->id(),
                'available_items' => $this->getAllowedTypes($this->fieldDefinition),
                'existing_items' => $items->getValue(),
                'fieldName' => $this->fieldDefinition->getName(),
              ],
            ],
          ],
        ],
      ];

    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Returns the sorted allowed types for a entity reference field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *  (optional) The field definition forwhich the allowed types should be
   *  returned, defaults to the current field.
   *
   * @return array
   *   A list of arrays keyed by the paragraph type machine name with the following properties.
   *     - label: The label of the paragraph type.
   *     - weight: The weight of the paragraph type.
   */
  public function getAllowedTypes(FieldDefinitionInterface $field_definition = NULL) {
    $return_bundles = [];
    $handler = $this->selectionManager->getSelectionHandler($field_definition ?: $this->fieldDefinition);
    if ($handler instanceof ParagraphSelection) {
      $return_bundles = $handler->getSortedAllowedTypes();
    }
    return $return_bundles;
  }

}
