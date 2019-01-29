<?php

namespace Drupal\su_humsci_profile\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Promotes a node.
 *
 * @Action(
 *   id = "node_clone_action",
 *   label = @Translation("Clone selected content"),
 *   type = "node"
 * )
 */
class CloneNode extends ActionBase {

  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // TODO: Implement access() method.
  }

  public function execute() {
    // TODO: Implement execute() method.
  }
}
