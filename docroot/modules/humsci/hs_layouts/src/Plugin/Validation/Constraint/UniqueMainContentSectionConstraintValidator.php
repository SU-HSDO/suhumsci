<?php

namespace Drupal\hs_layouts\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\LayoutTempstoreRepository;
use Drupal\layout_builder\SectionStorage\SectionStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the hs_layouts_unique_main_content_section constraint.
 */
class UniqueMainContentSectionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Section Storage Manager service.
   *
   * @var Drupal\layout_builder\SectionStorage\SectionStorageManager
   */
  private $sectionStorageManager;

  /**
   * Layout Tempstorage Repository service.
   *
   * @var Drupal\layout_builder\LayoutTempstoreRepository
   */
  private $layoutTempstoreRepository;

  /**
   * Create a new UniqueMainContentSectionConstraintValidator instance.
   *
   * @param Drupal\layout_builder\SectionStorage\SectionStorageManager $sectionStorageManager
   *   Section storage manager service.
   * @param Drupal\layout_builder\LayoutTempstoreRepository $layoutTempstoreRepository
   *   Layout Tempstore Repository service.
   */
  public function __construct(SectionStorageManager $sectionStorageManager, LayoutTempstoreRepository $layoutTempstoreRepository) {
    $this->sectionStorageManager = $sectionStorageManager;
    $this->layoutTempstoreRepository = $layoutTempstoreRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $node_context = EntityContext::fromEntity($value);
    $section_storage = $this->sectionStorageManager->load('overrides', [
      'entity' => $node_context,
      'view_mode' => new Context(new ContextDefinition('string'), 'default'),
    ]);
    $temp_storage = $this->layoutTempstoreRepository->get($section_storage);
    $main_content_found = FALSE;
    foreach ($temp_storage->getSections() as $section) {
      if ($section->getLayoutSettings()['main_content'] !== 'none') {
        if ($main_content_found) {
          $this->context->addViolation($constraint->notUnique);
          break;
        }
        else {
          $main_content_found = TRUE;
        }
      }
    }
    if (!$main_content_found) {
      $this->context->addViolation($constraint->notUnique);
    }
  }

}
