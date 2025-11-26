<?php

declare(strict_types=1);

namespace Drupal\hs_cmap\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueRequirementType constraint.
 */
class UniqueRequirementTypeConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a UniqueRequirementTypeConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint): void {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $item->getEntity();

    // Because we had to use hs_cmap_entity_base_field_info_alter() to add the
    // constraint on the name field, this validator will run on all terms.
    // Only validate for requirement_type vocabulary.
    if ($term->bundle() !== 'hs_curriculum_requirement_type') {
      return;
    }

    $field_name = $item->getFieldDefinition()->getName();
    $value = $item->value;
    $term_id = $term->id();

    // Check for duplicate colour.
    if (
      $field_name === 'field_hs_curriculum_course_color'
      && !empty($value)
      && $this->isDuplicate($field_name, $value, $term_id)
    ) {
      $this->context->addViolation($constraint->duplicateColour);
    }

    // Check for duplicate name.
    if (
      $field_name === 'name'
      && !empty($value)
      && $this->isDuplicate($field_name, $value, $term_id)
    ) {
      $this->context->addViolation($constraint->duplicateName);
    }
  }

  /**
   * Find terms in this vocabulary with dupes for the given field.
   *
   * @param string $field_name
   * @param mixed $value
   * @param string|null $exclude_term_id
   *
   * @return bool
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function isDuplicate(string $field_name, mixed $value, ?string $exclude_term_id): bool {
    $query = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'hs_curriculum_requirement_type')
      ->condition($field_name, $value);

    if ($exclude_term_id) {
      $query->condition('tid', $exclude_term_id, '!=');
    }

    return !empty($query->execute());
  }

}
