<?php

namespace Drupal\hs_capx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Capx importer entities.
 */
interface CapxImporterInterface extends ConfigEntityInterface {

  const IMPORT_PROFILES = 0;

  const IMPORT_PUBLICATIONS = 1;

  const IMPORT_BOTH = 2;

  /**
   * Get all workgroups as a string or as an array.
   *
   * @param bool $as_string
   *   Return as an comma delimited string.
   *
   * @return array|string
   *   List of workgroups.
   */
  public function getWorkgroups($as_string = FALSE);

  /**
   * Get all organizations as a string or as an array.
   *
   * @param bool $as_string
   *   Return as an comma delimited string.
   *
   * @return array|string
   *   List of organizations.
   */
  public function getOrganizations($as_string = FALSE);

  /**
   * If the importer should include children organizations.
   *
   * @return bool
   *   True to include children.
   */
  public function includeChildrenOrgs();

  /**
   * Get all CapX Urls.
   *
   * @return array
   *   Array of urls.
   */
  public function getCapxUrls();

  /**
   * Get configured field tag data.
   *
   * @param string $field_name
   *   Optionally specify a field name to collect data.
   *
   * @return array
   *   If field name provided, an array of term ids; no field name returns a
   *   keyed array of field names and their terms
   */
  public function getFieldTags($field_name = NULL);

  /**
   * Import profiles, publications, or both.
   *
   * @return int
   */
  public function importWhat();

  /**
   * If the importer should import profiles.
   *
   * @return bool
   */
  public function importProfiles();

  /**
   * If the importer should import Publications.
   *
   * @return bool
   */
  public function importPublications();

}
