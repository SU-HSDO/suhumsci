<?php

namespace Drupal\hs_capx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Capx importer entities.
 */
interface CapxImporterInterface extends ConfigEntityInterface {

  /**
   * @param bool $as_string
   *
   * @return array|string
   */
  public function getWorkgroups($as_string = FALSE);

  /**
   * @param bool $as_string
   *
   * @return array|string
   */
  public function getOrganizations($as_string = FALSE);

  /**
   * @return bool
   */
  public function includeChildrenOrgs();

  /**
   * @return array
   */
  public function getCapxUrls();

  /**
   * @param $field_name
   *
   * @return array
   */
  public function getFieldTags($field_name);

}
