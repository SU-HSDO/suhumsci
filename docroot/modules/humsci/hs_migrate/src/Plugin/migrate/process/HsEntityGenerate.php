<?php

namespace Drupal\hs_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;

/**
 * This plugin generates entities within the process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "hs_entity_generate"
 * )
 *
 * @see EntityGenerate
 *
 * This plugin adds to the EntityGenerate plugin by setting the properties and
 * field values to the new imported content. For example if a description on
 * an taxonomy term changes in the feed after the term is created locally, we
 * want to make sure that change gets into the taxonomy term.
 */
class HsEntityGenerate extends EntityGenerate {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $this->row = $row;
    $this->migrateExecutable = $migrateExecutable;
    // Creates an entity if the lookup determines it doesn't exist.
    if (!($result = parent::transform($value, $migrateExecutable, $row, $destinationProperty))) {
      $result = $this->generateEntity($value);
    }
    else {
      $this->entityValues($value, $result);
    }

    return $result;
  }

  /**
   * Set the entity values with new data form the importer.
   *
   * @param string $value
   *   Migration source value.
   * @param int $entity_id
   *   Entity ID that was found.
   */
  protected function entityValues($value, $entity_id) {
    try {
      $entity = $this->entityManager
        ->getStorage($this->lookupEntityType)
        ->load($entity_id);

      foreach ($this->entity($value) as $key => $entity_value) {
        $entity->{$key} = $entity_value;
      }
      $entity->save();
    }
    catch (\Exception $e) {
      // Nothing to do if the entity no longer exists or has issues with
      // incorrect mapping configurations.
    }
  }

}
