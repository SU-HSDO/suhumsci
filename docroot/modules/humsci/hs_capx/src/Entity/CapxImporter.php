<?php

namespace Drupal\hs_capx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Capx importer entity.
 *
 * @ConfigEntityType(
 *   id = "capx_importer",
 *   label = @Translation("Capx importer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hs_capx\CapxImporterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hs_capx\Form\CapxImporterForm",
 *       "edit" = "Drupal\hs_capx\Form\CapxImporterForm",
 *       "delete" = "Drupal\hs_capx\Form\CapxImporterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\hs_capx\CapxImporterHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "capx_importer",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/migrate/capx/{capx_importer}",
 *     "add-form" = "/admin/structure/migrate/capx/add",
 *     "edit-form" = "/admin/structure/migrate/capx/{capx_importer}/edit",
 *     "delete-form" = "/admin/structure/migrate/capx/{capx_importer}/delete",
 *     "collection" = "/admin/structure/migrate/capx"
 *   }
 * )
 */
class CapxImporter extends ConfigEntityBase implements CapxImporterInterface {

  /**
   * The Capx importer ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Capx importer label.
   *
   * @var string
   */
  protected $label;

  protected $organizations = [];

  protected $workgroups = [];

  protected $childOrganizations;

  protected $tagging = [];

  /**
   * {@inheritdoc}
   */
  public function getOrganizations() {
    return $this->organizations;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkgroups() {
    return $this->workgroups;
  }

  /**
   * {@inheritdoc}
   */
  public function includeChildrenOrgs() {
    return $this->childOrganizations;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapxUrl() {
    return '';
  }
  /**
   * {@inheritdoc}
   */
  public function getFieldTags($field_name) {
    if (isset($this->tagging[$field_name])) {
      return $this->tagging[$field_name];
    }
    return [];
  }

}
