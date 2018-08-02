<?php

namespace Drupal\mrc_helper\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Site\Settings;

/**
 * Provides a "dynamic" entity autocomplete form element.
 *
 * This form element allows you to create an entity autocomplete that works
 * with multiple types of entities. This wont be needed when
 * https://www.drupal.org/project/drupal/issues/2423093 is resolved.
 *
 * @see \Drupal\Core\Entity\Element\EntityAutocomplete
 *
 * @FormElement("dynamic_entity_autocomplete")
 */
class DynamicEntityAutocomplete extends Textfield {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    // An array keyed in the format of:
    // entity_type_id:
    //   selection_settings:
    //     ...selection settings
    //   selection_handler: handler
    $info['#target_types'] = NULL;

    array_unshift($info['#process'], [$class, 'processDynamicEntityAutocomplete']);
    $info['#element_validate'] = [[$class, 'validateDynamicEntityAutocomplete']];
    return $info;
  }

  /**
   * Process the dyanmic_entity_autocomplete widget.
   */
  public static function processDynamicEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#target_types'])) {
      throw new \InvalidArgumentException('Missing required #target_types parameter.');
    }
    // Serialize all of the settings for this element into state, so the route
    // can pull it back out. See EntityAutocomplete for details on this
    // approach.
    $autocomplete_data = serialize($element['#target_types']);
    $selection_settings_key = Crypt::hmacBase64($autocomplete_data, Settings::getHashSalt());
    $key_value_storage = static::getEntityAutocompleteKeyValueStore();
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $element['#target_types']);
    }
    $element['#autocomplete_route_name'] = 'mrc_helper';
    $element['#autocomplete_route_parameters'] = [
      'selection_settings_key' => $selection_settings_key,
    ];
    return $element;
  }

  /**
   * Validate the dynamic_auto_complete element.
   */
  public static function validateDynamicEntityAutocomplete(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // If the value is already an array, it may have been set as #value,
    // and thus should already be in the correct format.
    if (is_array($element['#value'])) {
      $form_state->setValueForElement($element, $element['#value']);
      return;
    }

    $element_value = [];
    $entity_ids_from_input = static::getEntityIdsByEntityTypeFromInput($element['#value']);

    foreach ($element['#target_types'] as $entity_type_id => $settings) {
      $options = [
        'target_type' => $entity_type_id,
        'handler' => $settings['selection_handler'],
        'handler_settings' => $settings['selection_settings'],
      ];
      // Validate all entities selected for the specific entity type.
      $handler = static::getSelectionManager()->getInstance($options);
      if (isset($entity_ids_from_input[$entity_type_id])) {
        $valid_ids = $handler->validateReferenceableEntities($entity_ids_from_input[$entity_type_id]);
        if ($invalid_ids = array_diff($entity_ids_from_input[$entity_type_id], $valid_ids)) {
          foreach ($invalid_ids as $invalid_id) {
            $form_state->setError($element, t('The referenced entity (%type: %id) does not exist.', ['%type' => $entity_type_id, '%id' => $invalid_id]));
          }
        }
        foreach ($valid_ids as $valid_entity_id) {
          $element_value[] = [
            'entity' => static::loadEntity($entity_type_id, $valid_entity_id)
          ];
        }
      }
    }
    $form_state->setValueForElement($element, $element_value);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // If the default value is set to an array of entities, create a string
    // for it.
    if ($input === FALSE && isset($element['#default_value']) && is_array($element['#default_value'])) {
      return static::getEntityLabels($element['#default_value']);
    }
  }

  /**
   * Converts an array of entity objects into a string of entity labels.
   *
   * This method is also responsible for checking the 'view label' access on the
   * passed-in entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entity objects.
   *
   * @return string
   *   A string of entity labels separated by commas.
   */
  public static function getEntityLabels(array $entities) {
    $entity_labels = [];
    foreach ($entities as $entity) {
      $label = ($entity->access('view label')) ? $entity->label() : t('- Restricted access -');
      $entity_labels[] = Tags::encode(sprintf('%s (%s:%s)', $label, $entity->getEntityTypeId(), $entity->id()));
    }
    return implode(', ', $entity_labels);
  }

  /**
   * Get the entity type IDs from some input.
   *
   * @param string $input
   *   The input.
   * @return array
   */
  public static function getEntityIdsByEntityTypeFromInput($input) {
    $tags = Tags::explode($input);
    $entities = [];
    foreach ($tags as $tag) {
      preg_match('/(?<type>\w+)\:(?<id>\d+)\)$/', $tag, $matches);
      if (!empty($matches['type']) && !empty($matches['id'])) {
        $entities[$matches['type']][] = $matches['id'];
      }
    }
    return $entities;
  }

  /**
   * Get the selection plugin manager.
   *
   * @return SelectionPluginManagerInterface
   *   The selection plugin manager.
   */
  protected static function getSelectionManager() {
    return \Drupal::service('plugin.manager.entity_reference_selection');
  }

  /**
   * Load an entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_id
   *   The entity id.
   *
   * @return EntityInterface
   *   A loaded entity.
   */
  protected static function loadEntity($entity_type_id, $entity_id) {
    return \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
  }

  /**
   * Get the entity autocomplete key/value store.
   *
   * @return KeyValueStoreInterface
   *   The key/value store.
   */
  protected static function getEntityAutocompleteKeyValueStore() {
    return \Drupal::keyValue('dynamic_entity_autocomplete');
  }

}
