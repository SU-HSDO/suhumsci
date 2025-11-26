<?php

declare(strict_types=1);

namespace Drupal\hs_cmap\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the Requirement Type taxonomy terms have unique colour and name.
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
   * Message for duplicate colour.
   *
   * @var string
   */
  public string $duplicateColour = 'This colour is already used by another requirement type. Each requirement type must have a unique colour.';

  /**
   * Message for duplicate name.
   *
   * @var string
   */
  public string $duplicateName = 'This name is already used by another requirement type. Each requirement type must have a unique name.';

}
