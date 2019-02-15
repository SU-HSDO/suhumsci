<?php

namespace Drupal\hs_actions\Plugin\Action\FieldClone;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface FieldCloneInterface
 *
 * @package Drupal\hs_actions\Plugin\Axction\FieldClone
 */
interface FieldCloneInterface extends PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name
   * @param array $config
   *
   * @return mixed
   */
  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $entity, $field_name, $config = []);

}
