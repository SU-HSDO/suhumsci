<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldType;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Plugin implementation of the 'display_mode_field' field type.
 *
 * @FieldType(
 *   id = "display_mode_field",
 *   label = @Translation("Display Mode Select"),
 *   description = @Translation("Allow the user to choose which display mode to display the entity."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class DisplayModeField extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $default_settings = $this->getSetting('allowed_values');

    $element['allowed_values'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Modes'),
      '#tree' => TRUE,
      '#element_validate' => [[$this, 'elementValidate']],
    ];

    // Todo: change this to dependency injection when
    // https://www.drupal.org/node/2053415 is resolved.
    /** @var \Drupal\Core\Entity\EntityDisplayRepository $display_repo */
    $display_repo = \Drupal::service('entity_display.repository');
    $view_modes = $display_repo->getViewModeOptionsByBundle($this->getEntity()
      ->getEntityTypeId(), $this->getEntity()->bundle());

    foreach ($view_modes as $view_mode_id => $label) {
      $element['allowed_values'][$view_mode_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow %label', ['%label' => $label]),
        '#default_value' => isset($default_settings[$view_mode_id]),
      ];
      $element['allowed_values'][$view_mode_id]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label for %label', ['%label' => $label]),
        '#default_value' => $default_settings[$view_mode_id] ?? NULL,
        '#states' => [
          'visible' => [
            ':input[name*="' . $view_mode_id . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
          'binary' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * Validation to clean up field values.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $form
   *   Complete form.
   */
  public function elementValidate(array $element, FormStateInterface $form_state, array $form) {
    $modes = &$form_state->getValue(['settings', 'allowed_values']);
    foreach ($modes as $mode_id => &$mode) {
      if (!$mode['enabled']) {
        unset($modes[$mode_id]);
        continue;
      }
      $mode = $mode['label'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * Get the display mode from the field value.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   Entity in question.
   *
   * @return string
   *   New display mode if found.
   */
  public static function getDisplayMode(FieldableEntityInterface $entity) {
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
      if (
        $field_definition->getType() == 'display_mode_field' &&
        $value = $entity->get($field_name)->getValue()
      ) {
        return $value[0]['value'];
      }
    }
    return '';
  }

}
