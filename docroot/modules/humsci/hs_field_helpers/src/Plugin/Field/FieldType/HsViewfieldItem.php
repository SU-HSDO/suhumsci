<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\viewfield\Plugin\Field\FieldType\ViewfieldItem;

/**
 * Class HsViewfieldItem
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldType
 */
class HsViewfieldItem extends ViewfieldItem {

  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['allow_title_customizing'] = 0;
    return $settings;
  }

  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    $form['allow_title_customizing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow customized view title'),
      '#description' => $this->t('Let the user choose to display the view title and customize it'),
      '#default_value' => $this->getSetting('allow_title_customizing'),
      '#weight' => -10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['show_title'] = [
      'description' => 'Show the title of the view.',
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ];
    $schema['columns']['override_title'] = [
      'description' => 'Override the title of the view.',
      'type' => 'int',
      'size' => 'small',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
    ];
    $schema['columns']['overridden_title'] = [
      'description' => 'Customize view title.',
      'type' => 'varchar',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['show_title'] = DataDefinition::create('integer')
      ->setLabel(t('Show View Title'))
      ->setDescription(t('Display the view title in render'));

    $properties['override_title'] = DataDefinition::create('integer')
      ->setLabel(t('Override View Title'))
      ->setDescription(t('Override the view title in render'));

    $properties['overridden_title'] = DataDefinition::create('string')
      ->setLabel(t('Overridden Title'))
      ->setDescription(t('Custom view title'));

    return $properties;
  }

}
