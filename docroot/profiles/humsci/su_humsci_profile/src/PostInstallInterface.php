<?php

namespace Drupal\su_humsci_profile;

/**
 * Interface PostInstallInterface.
 *
 * @package Drupal\su_humsci_profile
 */
interface PostInstallInterface {

  /**
   * Run various tasks after profile has been installed.
   */
  public function runTasks();

}
