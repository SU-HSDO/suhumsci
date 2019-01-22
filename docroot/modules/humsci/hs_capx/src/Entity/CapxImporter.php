<?php

namespace Drupal\hs_capx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\hs_capx\Capx;

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
 *   admin_permission = "administer capx settings",
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

  /**
   * Array of organization strings.
   *
   * @var array
   */
  protected $organizations = [];

  /**
   * Array of workgroup strings.
   *
   * @var array
   */
  protected $workgroups = [];

  /**
   * Include the child organizations.
   *
   * @var bool
   */
  protected $childOrganizations;

  /**
   * Keyed array of field tagging data.
   *
   * @var array
   */
  protected $tagging = [];

  /**
   * {@inheritdoc}
   */
  public function getOrganizations($as_string = FALSE) {
    return $as_string ? implode(',', $this->organizations) : $this->organizations;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkgroups($as_string = FALSE) {
    return $as_string ? implode(',', $this->workgroups) : $this->workgroups;
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
  public function getCapxUrls() {
    $urls = [];
    if ($organizations = $this->getOrganizations(TRUE)) {
      $urls[] = Capx::getOrganizationUrl($organizations, $this->includeChildrenOrgs());
    }
    if ($workgroups = $this->getWorkgroups(TRUE)) {
      $urls[] = Capx::getWorkgroupUrl($workgroups);
    }
    return $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTags($field_name = NULL) {
    if (!$field_name) {
      return $this->tagging;
    }
    if (isset($this->tagging[$field_name])) {
      return $this->tagging[$field_name];
    }
    return [];
  }

}
