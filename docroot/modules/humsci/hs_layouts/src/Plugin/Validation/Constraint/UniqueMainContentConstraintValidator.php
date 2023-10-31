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
 * Validates the hs_layouts_unique_main_content constraint.
 */
class UniqueMainContentConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Section Storage Manager service.
   *
   * @var Drupal\layout_builder\SectionStorage\SectionStorageManager
   */
  private $storageManager;

  /**
   * Layout Tempstorage Repository service.
   *
   * @var Drupal\layout_builder\LayoutTempstoreRepository
   */
  private $tempstoreRepository;

  /**
   * Create a new UniqueMainContentConstraintValidator instance.
   *
   * @param Drupal\layout_builder\SectionStorage\SectionStorageManager $storageManager
   *   Section storage manager service.
   * @param Drupal\layout_builder\LayoutTempstoreRepository $tempstoreRepository
   *   Layout Tempstore Repository service.
   */
  public function __construct(SectionStorageManager $storageManager, LayoutTempstoreRepository $tempstoreRepository) {
    $this->storageManager = $storageManager;
    $this->tempstoreRepository = $tempstoreRepository;
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
    $section_storage = $this->storageManager->load('overrides', [
      'entity' => $node_context,
      'view_mode' => new Context(new ContextDefinition('string'), 'default'),
    ]);
    if ($section_storage && $section_storage->isOverridden()) {
      $temp_storage = $this->tempstoreRepository->get($section_storage);
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

}
