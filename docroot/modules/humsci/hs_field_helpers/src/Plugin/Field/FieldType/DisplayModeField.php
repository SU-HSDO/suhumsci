<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'display_mode_field' field type.
 *
 * @FieldType(
 *   id = "display_mode_field",
 *   label = @Translation("Display Mode Select"),
 *   description = @Translation("Allow the user to choose which display mode to
 *   display the entity."), default_widget = "display_mode_widget",
 *   default_formatter = "display_mode_formatter"
 * )
 */
class DisplayModeField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'display_modes' => [],
      ] + parent::defaultStorageSettings();
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
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $default_settings = $this->getSetting('display_modes');
    dpm($default_settings);

    $elements['display_modes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Modes'),
      '#tree' => TRUE,
    ];

    $view_modes_ids = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', $this->getEntity()->getEntityTypeId())
      ->execute();
    $view_modes = \Drupal::entityTypeManager()
      ->getStorage('entity_view_mode')
      ->loadMultiple($view_modes_ids);

    foreach ($view_modes as $view_mode) {
      $elements['display_modes'][$view_mode->id()]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Allow %label', ['%label' => $view_mode->label()]),
        '#default_value' => $default_settings[$view_mode->id()]['enabled'] ?? NULL,
      ];
      $elements['display_modes'][$view_mode->id()]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label for %label', ['%label' => $view_mode->label()]),
        '#default_value' => $default_settings[$view_mode->id()]['label'] ?? NULL,
        '#states' => [
          'visible' => [
            ':input[name*="' . $view_mode->id() . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
//    $default_settings = $this->getSetting('display_modes');
//    dpm($default_settings);
//    $elements['display_modes'] = [
//      '#type' => 'fieldset',
//      '#title' => $this->t('Display Modes'),
//      '#tree' => TRUE,
//    ];
//
//    $view_modes_ids = \Drupal::entityQuery('entity_view_mode')
//      ->condition('targetEntityType', $this->getEntity()->getEntityTypeId())
//      ->execute();
//    $view_modes = \Drupal::entityTypeManager()
//      ->getStorage('entity_view_mode')
//      ->loadMultiple($view_modes_ids);
//
//    foreach ($view_modes as $view_mode) {
//      $elements['display_modes'][$view_mode->id()]['enabled'] = [
//        '#type' => 'checkbox',
//        '#title' => $this->t('Allow %label', ['%label' => $view_mode->label()]),
//        '#default_value' => $default_settings[$view_mode->id()]['enabled'] ?? NULL,
//        '#disabled' => $has_data,
//      ];
//      $elements['display_modes'][$view_mode->id()]['label'] = [
//        '#type' => 'textfield',
//        '#title' => $this->t('Label for %label', ['%label' => $view_mode->label()]),
//        '#default_value' => $default_settings[$view_mode->id()]['label'] ?? NULL,
//        '#disabled' => $has_data,
//        '#states' => [
//          'visible' => [
//            ':input[name*="' . $view_mode->id() . '"]' => ['checked' => TRUE],
//          ],
//        ],
//      ];
//    }
//
//    //    $elements['max_length'] = [
//    //      '#type' => 'number',
//    //      '#title' => t('Maximum length'),
//    //      '#default_value' => $this->getSetting('max_length'),
//    //      '#required' => TRUE,
//    //      '#description' => t('The maximum length of the field in characters.'),
//    //      '#min' => 1,
//    //      '#disabled' => $has_data,
//    //    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function storageSettingsToConfigData(array $settings) {
    foreach ($settings['display_modes'] as $id => $mode) {
      if (!$mode['enabled']) {
        unset($settings['display_modes'][$id]);
      }
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
