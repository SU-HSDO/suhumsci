<?php

/**
 * @file
 * Read-only video embed field audit, executed by the multisite report command.
 */

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\field\FieldStorageConfigInterface;

$entity_type_manager = \Drupal::entityTypeManager();
$deleted_fields = \Drupal::service('entity_field.deleted_fields_repository');
$storages = $entity_type_manager->getStorage('field_storage_config')->loadByProperties(['type' => 'video_embed_field']);
$storages = array_merge(array_values($storages), array_values($deleted_fields->getFieldStorageDefinitions()));
$fields = [];
foreach ($storages as $storage) {
  if ($storage->getType() !== 'video_embed_field') {
    continue;
  }
  $entity_storage = $entity_type_manager->getStorage($storage->getTargetEntityTypeId());
  if (!$entity_storage instanceof FieldableEntityStorageInterface || $storage->hasCustomStorage()) {
    throw new \RuntimeException('Cannot count field data for ' . $storage->getName());
  }
  $fields[] = [
    'entity_type' => $storage->getTargetEntityTypeId(),
    'field_name' => $storage->getName(),
    'storage_uuid' => $storage->getUniqueStorageIdentifier(),
    'deleted' => $storage->isDeleted(),
    'bundles' => $storage instanceof FieldStorageConfigInterface ? array_values($storage->getBundles()) : [],
    // Core counts distinct entities across revisions, including deleted data.
    'entities_with_data' => $entity_storage->countFieldData($storage),
  ];
}

$deleted_instances = [];
foreach ($deleted_fields->getFieldDefinitions() as $field) {
  if ($field->getType() === 'video_embed_field') {
    $deleted_instances[] = $field->getTargetEntityTypeId() . '.' . $field->getTargetBundle() . '.' . $field->getName();
  }
}

$config_storage = \Drupal::service('config.storage');
$media_types = [];
$config_references = [];
foreach ($config_storage->listAll() as $name) {
  // Installation alone does not indicate usage of the module's field/plugins.
  if ($name === 'core.extension') {
    continue;
  }
  $data = $config_storage->read($name);
  if ($data === FALSE) {
    throw new \RuntimeException('Could not read configuration ' . $name);
  }
  if (str_starts_with($name, 'media.type.') && ($data['source'] ?? NULL) === 'video_embed_field') {
    $media_types[] = $data['id'];
  }
  $serialized = serialize($data);
  if (str_contains($serialized, 'video_embed_field') || str_contains($serialized, 'video_embed_media') || str_contains($serialized, 'field_media_video_embed_field')) {
    $config_references[] = $name;
  }
}

$modules = [];
foreach (['video_embed_field', 'video_embed_media'] as $module) {
  $modules[$module] = \Drupal::moduleHandler()->moduleExists($module);
}

echo json_encode([
  'status' => $fields || $deleted_instances || $media_types || $config_references ? 'review' : 'clear',
  'modules' => $modules,
  'fields' => $fields,
  'deleted_field_instances' => $deleted_instances,
  'media_types' => $media_types,
  'config_references' => $config_references,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
