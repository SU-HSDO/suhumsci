<?php

namespace Drupal\hs_bugherd;

/**
 * Interface BugherdJiraInterface
 *
 * @package Drupal\hs_bugherd
 */
interface BugherdJiraInterface {

  /**
   * @return mixed
   */
  public function updateBugherdTicket();

  /**
   * @return mixed
   */
  public function updateJiraTicket();

}
