<?php

namespace Drupal\hs_blocks\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\hs_blocks\VisibilityRuleEvaluator;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Hides Layout Builder components whose visibility rule matches.
 */
class VisibilityRuleSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new VisibilityRuleSubscriber object.
   *
   * @param \Drupal\hs_blocks\VisibilityRuleEvaluator $evaluator
   *   The visibility rule evaluator.
   */
  public function __construct(protected VisibilityRuleEvaluator $evaluator) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after core's block subscriber (priority 100) so the block is built.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = ['onSectionComponentBuild', 50];
    return $events;
  }

  /**
   * Empties the build of a component when its visibility rule hides it.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Layout builder section event.
   */
  public function onSectionComponentBuild(SectionComponentBuildRenderArrayEvent $event): void {
    $rule = $event->getComponent()->get('visibility_rule');
    if (empty($rule) || !is_array($rule)) {
      return;
    }

    $contexts = $event->getContexts();
    $entity = isset($contexts['layout_builder.entity']) ? $contexts['layout_builder.entity']->getContextValue() : NULL;
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $metadata = new CacheableMetadata();
    $is_hidden = $this->evaluator->isHidden($rule, $entity, $metadata);
    $event->getCacheableMetadata()->addCacheableDependency($metadata);
    if (!$is_hidden) {
      return;
    }

    // Editors must still reach the block's contextual links, so it is only
    // flagged while previewing.
    if ($event->inPreview()) {
      $build = $event->getBuild();
      $build['#attributes']['class'][] = 'hs-blocks-conditionally-hidden';
      $build['#attached']['library'][] = 'hs_blocks/visibility.admin';
      $event->setBuild($build);
      return;
    }

    $event->setBuild([]);
  }

}
