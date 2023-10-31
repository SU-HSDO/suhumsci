<?php

namespace Drupal\hs_layouts\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityDisplayRepository;
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
   * Layout Entity Display Repository service.
   *
   * @var Drupal\Core\Entity\EntityDisplayRepository
   */
  private $displayRepository;

  /**
   * Create a new UniqueMainContentConstraintValidator instance.
   *
   * @param Drupal\layout_builder\SectionStorage\SectionStorageManager $storageManager
   *   Section storage manager service.
   * @param Drupal\layout_builder\LayoutTempstoreRepository $tempstoreRepository
   *   Layout Tempstore Repository service.
   * @param Drupal\Core\Entity\EntityDisplayRepository $displayRepository
   *   Layout Tempstore Repository service.
   */
  public function __construct(SectionStorageManager $storageManager, LayoutTempstoreRepository $tempstoreRepository, EntityDisplayRepository $displayRepository) {
    $this->storageManager = $storageManager;
    $this->tempstoreRepository = $tempstoreRepository;
    $this->displayRepository = $displayRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('layout_builder.tempstore_repository'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($node, Constraint $constraint) {
    $view_display = $this->displayRepository->getViewDisplay('node', $node->bundle());
    $lb_overridable = $view_display->isLayoutBuilderEnabled() && $view_display->isOverridable();
    if ($lb_overridable) {
      $node_context = EntityContext::fromEntity($node);
      $section_storage = $this->storageManager->load('overrides', [
        'entity' => $node_context,
        'view_mode' => new Context(new ContextDefinition('string'), 'default'),
      ]);
      if ($section_storage && $this->tempstoreRepository->has($section_storage)) {
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

}
