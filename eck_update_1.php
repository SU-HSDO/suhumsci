<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\hs_entities\Entity\HsEntityType;


echo "======================================================================\n";
echo "Running ECK Update Hook 1: Create new entities to migrate ECKs.\n";
echo "Creates an hs_entity for each eck_entity with the same bundles and fields.\n";


$config_factory = \Drupal::configFactory();
/** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
$entity_field_manager = \Drupal::service('entity_field.manager');
$field_map = $entity_field_manager->getFieldMap();
$names = $config_factory->listAll('eck.eck_entity_type.');

foreach ($names as $name) {
  $eck_type = $config_factory->get($name);
  $eck_name = $eck_type->get('id');
  $bundles = $config_factory->listAll("eck.eck_type.$eck_name.");
  foreach ($bundles as $bundle) {
    $eck_bundle = $config_factory->get($bundle);
    $bundle_id = $eck_bundle->get('type');
    $new_bundle_id = substr("{$eck_name}__$bundle_id", 0, 32);

    HsEntityType::create([
      'id' => $new_bundle_id,
      'label' => $eck_type->get('label') . ': ' . $eck_bundle->get('name'),
    ])->save();
    echo "\nCreated new bundle: " . $eck_type->get('label') . ": " . $eck_bundle->get('name') . "\n";

    foreach ($field_map[$eck_name] as $field_name => $field_info) {

      if (in_array($bundle_id, $field_info['bundles'])) {
        /** @var \Drupal\field\FieldStorageConfigInterface $storage */
        $storage = FieldStorageConfig::loadByName($eck_name, $field_name);
        /** @var \Drupal\Core\Field\FieldConfigInterface $config */
        $config = FieldConfig::loadByName($eck_name, $bundle_id, $field_name);

        if (!$storage || !$config) {
          continue;
        }

        if (!FieldStorageConfig::loadByName('hs_entity_type', $field_name)) {
          FieldStorageConfig::create([
            'type' => $storage->getType(),
            'field_name' => $field_name,
            'entity_type' => 'hs_entity',
            'settings' => $storage->getSettings(),
            'cardinality' => $storage->getCardinality(),
          ])->save();
          echo 'Created new storage config: ' . $field_name . '\n';
        }

        if (!FieldConfig::loadByName('hs_entity_type', $new_bundle_id, $field_name)) {
          FieldConfig::create([
            'field_type' => $config->getType(),
            'entity_type' => 'hs_entity',
            'bundle' => $new_bundle_id,
            'field_name' => $field_name,
            'label' => $config->label(),
            'settings' => $config->getSettings(),
          ])->save();
          echo 'Created new field config: ' . $new_bundle_id  . ':' . $field_name . '\n';
        }
      }
    }
  }
}

?>
