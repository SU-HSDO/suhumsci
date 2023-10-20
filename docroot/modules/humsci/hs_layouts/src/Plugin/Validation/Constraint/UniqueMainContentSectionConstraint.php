<?php

namespace Drupal\hs_layouts\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the layout builder config only has one main content section.
 *
 * @Constraint(
 *   id = "hs_layouts_unique_main_content_section",
 *   label = @Translation("Unique Main Content Section", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueMainContentSectionConstraint extends Constraint {

  /**
   * Message shown when multiple regions contain the main content id.
   *
   * @var string
   */
  public $notUnique = 'Only one region should contain the main content id.';

}
