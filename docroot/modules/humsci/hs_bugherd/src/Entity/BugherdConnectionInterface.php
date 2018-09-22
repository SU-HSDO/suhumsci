<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Bugherd Connection entities.
 */
interface BugherdConnectionInterface extends ConfigEntityInterface {

  /**
   * Get the Jira project ID.
   *
   * @return string
   */
  public function getJiraProject();

  /**
   * Get the bugherd project ID.
   *
   * @return int
   */
  public function getBugherdProject();

  /**
   * Get the status map from bugherd to jira status.
   *
   * @return array
   *   Keyed array: bugherd status => jira statuses.
   */
  public function getStatusMap();

  /**
   * Get all the urls configured for the entity.
   *
   * @return array
   *   List of urls.
   */
  public function getUrls();

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

  /**
   * Update Bugherd ticket that corresponds to the given Jira ticket.
   *
   * @param array $jira_data
   *   Data from jira webhook.
   *
   * @return mixed
   */
  public function updateBugherdTicket(array $jira_data);

  /**
   * Update Jira ticket that corresponds to the Bugherd ticket.
   *
   * @param array $bugherd_data
   *   Data from bugherd webhook.
   */
  public function updateJiraTicket(array $bugherd_data);

}
