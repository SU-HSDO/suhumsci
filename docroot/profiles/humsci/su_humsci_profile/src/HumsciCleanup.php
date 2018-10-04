<?php

namespace Drupal\su_humsci_profile;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Entity\View;

/**
 * Class HumsciCleanup.
 *
 * @package Drupal\su_humsci_profile
 */
class HumsciCleanup {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * HumsciCleanup constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  public function test() {
    $cleanup = \Drupal::service('su_humsci_profile.cleanup');
    $cleanup->deleteField('node', 'field_hs_person_faculty_interest');
  }

  /**
   * Completely delete a field from the database.
   *
   * @param string $entity_type
   *   Entity type id.
   * @param string $field_name
   *   Machine name of field.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteField($entity_type, $field_name) {
    $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

    if (!$field_storage) {
      return;
    }
    $this->deleteFieldFromViews($entity_type, $field_name);
    $field_configs = [];

    foreach (array_keys($this->bundleInfo->getBundleInfo($entity_type)) as $bundle) {
      if ($field_config = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
        $field_configs[$field_config->id()] = $field_config;
      }
    }

    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    foreach ($field_configs as $field_config) {
//      field_purge_field($field_config);
//      $field_config->delete();
    }
  }

  /**
   * Delete a field from any view it might be on.
   *
   * @param string $field_name
   *   Field machine name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteFieldFromViews($entity_type, $field_name) {
    /** @var \Drupal\views\Entity\View $view */
    foreach (View::loadMultiple(['a_test']) as $view) {
      $changed = FALSE;
      $displays = $view->get('display');
      foreach ($displays as &$display) {
        unset($display['display_options']['row']['pattern_mapping']["views_row:$field_name"]);

        $keys = ['fields', 'filters', 'sorts', 'arguments', 'relationships'];

        foreach ($keys as $key) {
          if (!empty($display['display_options'][$key])) {
            foreach ($display['display_options'][$key] as $item_key => $item) {
              if ($item['table'] == "{$entity_type}__$field_name") {
                $changed = TRUE;
                unset($display['display_options'][$key][$item_key]);
              }
            }
          }
        }
      }

      if ($changed) {
        $view->set('display', $displays);
        $view->save();
      }
    }
  }

}
