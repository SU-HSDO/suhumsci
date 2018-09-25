<?php

namespace Drupal\hs_bugherd\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Bugherd Connection entity.
 *
 * @ConfigEntityType(
 *   id = "bugherd_connection",
 *   label = @Translation("Bugherd Connection"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hs_bugherd\BugherdConnectionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hs_bugherd\Form\BugherdConnectionForm",
 *       "edit" = "Drupal\hs_bugherd\Form\BugherdConnectionForm",
 *       "delete" = "Drupal\hs_bugherd\Form\BugherdConnectionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bugherd",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/bugherd/{bugherd_connection}",
 *     "add-form" = "/admin/config/services/bugherd/add",
 *     "edit-form" = "/admin/config/services/bugherd/{bugherd_connection}/edit",
 *     "delete-form" = "/admin/config/services/bugherd/{bugherd_connection}/delete",
 *     "collection" = "/admin/config/services/bugherd"
 *   }
 * )
 */
class BugherdConnection extends ConfigEntityBase implements BugherdConnectionInterface {

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
   * Project ID in Bugherd.
   *
   * @var int
   */
  protected $bugherdProject;

  /**
   * Project Key in Jira.
   *
   * @var string
   */
  protected $jiraProject;

  /**
   * Associative array of Bugherd status to Jira status.
   *
   * @var array
   */
  protected $statusMap = [];

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
  public function getBugherdStatus($jira_status) {
    foreach ($this->statusMap as $bugherd_status => $jira) {
      $jira = explode(',', $jira);
      if (in_array($jira_status, $jira)) {
        return $bugherd_status;
      }
    }
  }

}
