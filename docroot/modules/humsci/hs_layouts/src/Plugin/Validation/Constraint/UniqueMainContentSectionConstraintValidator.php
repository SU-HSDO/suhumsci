<?php

namespace Drupal\hs_layouts\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the hs_layouts_unique_main_content_section constraint.
 */
class UniqueMainContentSectionConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    dump($value);
    if ($value->hasField('layout_builder__layout')) {
      dump($value->get('layout_builder__layout'));
      $this->context->addViolation($constraint->notUnique);
      // $main_content_found = FALSE;
      // foreach ($value->get('layout_builder__layout')->getSections() as $section) {
      //   dump($section->getLayoutSettings());
      //   if ($section->getLayoutSettings()['main_content'] !== 'none') {
      //     if ($main_content_found) {
      //       $this->context->addViolation($constraint->notUnique);
      //       break;
      //     }
      //     else {
      //       $main_content_found = TRUE;
      //     }
      //   }
      // }
    }
  }

}
