<?php

namespace Drupal\hs_capx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Capx importer entities.
 */
interface CapxImporterInterface extends ConfigEntityInterface {

  public function getWorkgroups();

  public function getOrganizations();

  public function includeChildrenOrgs();

  public function getCapxUrl();

  public function getFieldTags($field_name);

}
