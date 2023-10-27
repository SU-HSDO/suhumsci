<?php

namespace Drupal\hs_layouts\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the layout builder config only has one main content section.
 *
 * @Constraint(
 *   id = "hs_layouts_unique_main_content",
 *   label = @Translation("Unique Main Content", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueMainContentConstraint extends Constraint {

  /**
   * Message shown when multiple regions contain the main content id.
   *
   * @var string
   */
  public $notUnique = 'Exactly one region should contain the main content id.';

}
