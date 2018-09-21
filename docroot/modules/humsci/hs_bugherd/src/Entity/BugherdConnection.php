<?php

namespace Drupal\hs_bugherd\Entity;

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
class BugherdConnection extends BugherdConnectionBase {

  /**
   * {@inheritdoc}
   */
  public function updateJiraTicket() {
    // TODO: Implement updateJiraTicket() method.
  }

  /**
   * {@inheritdoc}
   */
  public function updateBugherdTicket() {
    // TODO: Implement updateBugherdTicket() method.
  }

}
