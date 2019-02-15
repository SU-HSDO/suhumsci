<?php

namespace Drupal\hs_actions\Plugin\Action\FieldClone;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface FieldCloneInterface for field clone plugins.
 *
 * @package Drupal\hs_actions\Plugin\Axction\FieldClone
 */
interface FieldCloneInterface extends PluginFormInterface, ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Alter the cloned entity field values.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $original_entity
   *   Original entity.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $new_entity
   *   New cloned entity, before saving.
   * @param string $field_name
   *   Field name to be altered.
   * @param array $config
   *   Array of form submitted config values.
   */
  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $new_entity, $field_name, array $config = []);

}
