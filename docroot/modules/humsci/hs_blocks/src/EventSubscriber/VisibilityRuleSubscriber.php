<?php

namespace Drupal\hs_blocks\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\hs_blocks\VisibilityRuleEvaluator;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
    // On the live page, run before core's block subscriber (priority 100) so a
    // hidden block is never built, skipping its views queries and entity loads.
    // In the Layout Builder preview the block must stay built so editors can
    // reach its contextual links, so it is flagged after core builds it.
    $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = [
      ['onBuildLive', 200],
      ['onBuildPreview', 50],
    ];
    // Carry a date rule's short max-age out to the response, after core has set
    // the page's Cache-Control (priority 0).
    $events[KernelEvents::RESPONSE][] = ['onResponse', -10];
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

    if ($is_hidden) {
      // Don't let core build a block that is hidden.
      $event->setBuild([]);
      $event->stopPropagation();
    }
  }

  /**
   * Lowers the response's cache life to a date rule's short max-age.
   *
   * A date rule shortens the block's cache life, but core sends proxies the
   * fixed page max-age, not that shorter one, so a page behind Varnish stays
   * stale until the date passes. Forwarding the bubbled max-age onto the
   * response lets Varnish and the browser expire it when the rule flips.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // A finite, positive max-age is the only thing that shortens caching;
    // permanent (-1) and uncacheable (0) leave the response alone.
    $max_age = $response->getCacheableMetadata()->getCacheMaxAge();
    if ($max_age <= 0) {
      return;
    }

    // Only tighten an already-cacheable response: never make an uncacheable
    // page cacheable, and keep a shorter lifetime set elsewhere.
    $current = $response->getMaxAge();
    if ($current !== NULL && $current > $max_age) {
      $response->setSharedMaxAge($max_age);
      $response->setMaxAge($max_age);
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

    [$rule] = $this->resolve($event);
    if (!$rule) {
      return;
    }

    // Flag any block that has a visibility rule so editors always see it,
    // whether or not the rule currently hides it on the live site. The block
    // stays fully rendered and legible; only a small badge is added.
    $build = $event->getBuild();
    $build['#attributes']['class'][] = 'hs-blocks-has-visibility-rule';
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
