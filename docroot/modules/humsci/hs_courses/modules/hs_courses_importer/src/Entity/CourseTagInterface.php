<?php

namespace Drupal\hs_courses_importer\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Course Tag Translation entities.
 */
interface CourseTagInterface extends ConfigEntityInterface {

  public function tag();

}
