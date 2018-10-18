<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Bugherd Connection entities.
 */
interface BugherdConnectionInterface extends ConfigEntityInterface {

  /**
   * Get the Jira project key.
   *
   * @return string
   *   Jira project key.
   */
  public function getJiraProject();

  /**
   * Get the bugherd project ID.
   *
   * @return int
   *   Bugherd Project ID.
   */
  public function getBugherdProject();

  /**
   * Get the status map from bugherd to jira status.
   *
   * @return array
   *   Associative array: bugherd status => jira statuses.
   */
  public function getStatusMap();

  /**
   * Get the appropriate bugherd status mapped to the given jira status.
   *
   * @param int $jira_status
   *   The status ID in Jira.
   *
   * @return string|null
   *   Bugherd status.
   */
  public function getBugherdStatus($jira_status);

}
