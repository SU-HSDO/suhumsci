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
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "organizations",
 *     "workgroups",
 *     "childOrganizations",
 *     "tagging",
 *     "importWhat"
 *   }
 * )
 */
class CapxImporter extends ConfigEntityBase implements CapxImporterInterface {

  /**
   * How many profiles for each url.
   */
  const URL_CHUNKS = 25;

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
   * Import profiles, publications or both.
   *
   * @var int
   */
  protected $importWhat;

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
      $url = Capx::getOrganizationUrl($organizations, $this->includeChildrenOrgs());
      $urls = self::getUrlChunks($url);
    }
    if ($workgroups = $this->getWorkgroups(TRUE)) {
      $url = Capx::getWorkgroupUrl($workgroups);
      $urls = array_merge($urls, self::getUrlChunks($url));
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

  /**
   * {@inheritdoc}
   */
  public function importWhat() {
    return $this->importWhat ?? self::IMPORT_PROFILES;
  }

  /**
   * {@inheritdoc}
   */
  public function importProfiles() {
    return $this->importWhat() != self::IMPORT_PUBLICATIONS;
  }

  /**
   * {@inheritdoc}
   */
  public function importPublications() {
    return $this->importWhat() != self::IMPORT_PROFILES;
  }

  /**
   * Break up the url into multiple urls based on the number of results.
   *
   * @param string $url
   *   Cap API Url.
   *
   * @return string[]
   *   Array of Cap API Urls.
   */
  protected static function getUrlChunks($url) {
    /** @var \Drupal\hs_capx\Capx $capx */
    $capx = \Drupal::service('capx');
    $count = (int) $capx->getTotalProfileCount($url);
    $number_chunks = ceil($count / self::URL_CHUNKS);

    if ($number_chunks <= 1) {
      return ["$url&ps=" . self::URL_CHUNKS];
    }

    $urls = [];
    for ($i = 1; $i <= $number_chunks; $i++) {
      $urls[] = "$url&p=$i&ps=" . self::URL_CHUNKS;
    }
    return $urls;
  }

}
