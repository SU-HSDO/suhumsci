<?php

declare(strict_types=1);

namespace Drupal\hs_cmap\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the Requirement Type taxonomy terms have unique color and name.
 *
 * This prevents a11y issues.
 *
 * @Constraint(
 *   id = "UniqueRequirementType",
 *   label = @Translation("Unique Requirement Type", context = "Validation"),
 * )
 */
class UniqueRequirementTypeConstraint extends Constraint {

  /**
   * Message for duplicate color.
   *
   * @var string
   */
  public string $duplicateColor = 'This color is already used by another requirement type. Choose a unique color.';

  /**
   * Message for duplicate name.
   *
   * @var string
   */
  public string $duplicateName = 'This name is already used by another requirement type. Choose a unique name.';

}
