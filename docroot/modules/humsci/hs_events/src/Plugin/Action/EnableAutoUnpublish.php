<?php

namespace Drupal\hs_events\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Enables auto-unpublish field on events.
 *
 * @Action(
 *   id = "hs_events_enable_auto_unpublish",
 *   label = @Translation("Auto-unpublish once event is past"),
 *   type = "node"
 * )
 */
class EnableAutoUnpublish extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity && $entity->hasField('field_auto_unpublish')) {
      $entity->set('field_auto_unpublish', TRUE);
      $entity->save();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
