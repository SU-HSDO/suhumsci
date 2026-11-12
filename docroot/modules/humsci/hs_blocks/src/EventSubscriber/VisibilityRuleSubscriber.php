<?php

namespace Drupal\hs_blocks\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\hs_blocks\VisibilityFlipTracker;
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
   * @param \Drupal\hs_blocks\VisibilityFlipTracker $flipTracker
   *   The date-rule flip tracker.
   */
  public function __construct(protected VisibilityRuleEvaluator $evaluator, protected VisibilityFlipTracker $flipTracker) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // On the live page, run before core's block subscriber (priority 100) so a
    // hidden block is never built, skipping its views queries and entity loads.
    // In the Layout Builder preview the block must stay built so editors can
    // reach its contextual links, so it is flagged after core builds it.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = [
      ['onBuildLive', 200],
      ['onBuildPreview', 50],
    ];
    return $events;
  }

  /**
   * Removes a component from the live page when its visibility rule hides it.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Layout builder section event.
   */
  public function onBuildLive(SectionComponentBuildRenderArrayEvent $event): void {
    if ($event->inPreview()) {
      return;
    }

    [$rule, $entity] = $this->resolve($event);
    if (!$entity) {
      return;
    }

    $metadata = new CacheableMetadata();
    $is_hidden = $this->evaluator->isHidden($rule, $entity, $metadata);
    $event->getCacheableMetadata()->addCacheableDependency($metadata);

    // A date rule shortens the cache lifetime so the block flips when the date
    // passes. Drupal's own caches honor that, but Acquia's Varnish does not, so
    // record the flip for cron to invalidate the entity's cache tag then.
    $this->flipTracker->record($entity->getCacheTags(), $metadata->getCacheMaxAge());

    if ($is_hidden) {
      // Empty the build and stop core from building a block that is hidden.
      $event->setBuild([]);
      $event->stopPropagation();
    }
  }

  /**
   * Flags a hidden component in the Layout Builder preview.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Layout builder section event.
   */
  public function onBuildPreview(SectionComponentBuildRenderArrayEvent $event): void {
    if (!$event->inPreview()) {
      return;
    }

    [$rule, $entity] = $this->resolve($event);
    if (!$entity) {
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
    $build = $event->getBuild();
    $build['#attributes']['class'][] = 'hs-blocks-conditionally-hidden';
    $build['#attached']['library'][] = 'hs_blocks/visibility.admin';
    $event->setBuild($build);
  }

  /**
   * Resolves a component's visibility rule and the entity it applies to.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   Layout builder section event.
   *
   * @return array
   *   A [rule, entity] pair. The entity is NULL when there is no rule to
   *   evaluate or no fieldable entity to evaluate it against.
   */
  protected function resolve(SectionComponentBuildRenderArrayEvent $event): array {
    $rule = $event->getComponent()->get('visibility_rule');
    if (empty($rule) || !is_array($rule)) {
      return [NULL, NULL];
    }

    return [$rule, VisibilityRuleEvaluator::getContextEntity($event->getContexts())];
  }

}
