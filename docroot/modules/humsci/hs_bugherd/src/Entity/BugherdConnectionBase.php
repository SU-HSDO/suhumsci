<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class BugherdConnection
 *
 * @package Drupal\hs_bugherd\Entity
 */
abstract class BugherdConnectionBase extends ConfigEntityBase implements BugherdConnectionInterface {

  /**
   * The Bugherd Connection ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Bugherd Connection label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var int
   */
  protected $bugherdProject;

  /**
   * @var string
   */
  protected $jiraProject;

  /**
   * @var array
   */
  protected $urls;

  /**
   * @var array
   */
  protected $statusMap;

  /**
   * {@inheritdoc}
   */
  public function getBugherdProject() {
    return $this->bugherdProject;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMap() {
    return $this->statusMap;
  }

  /**
   * {@inheritdoc}
   */
  public function getJiraProject() {
    return $this->jiraProject;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls() {
    return $this->urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getBugherdStatus($jira_status) {
    foreach ($this->statusMap as $bugherd_status => $jira) {
      $jira = explode(',', $jira);
      if (in_array($jira_status, $jira)) {
        return $bugherd_status;
      }
    }
  }

}
