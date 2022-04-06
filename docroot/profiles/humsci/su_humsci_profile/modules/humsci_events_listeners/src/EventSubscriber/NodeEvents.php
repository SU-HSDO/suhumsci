<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for block entities.
 */
class NodeEvents implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityHookEvents::ENTITY_PRE_SAVE => 'entityPresave',
      EntityHookEvents::ENTITY_DELETE => 'entityDelete',
    ];
  }

  /**
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *
   * @return void
   */
  public function entityPresave(EntityPresaveEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  /**
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *
   * @return void
   */
  public function entityDelete(EntityPresaveEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  protected static function flushEntityCaches(EntityInterface $entity) {
    if ($entity instanceof NodeInterface) {
      Cache::invalidateTags(['config:search_api.index.default_index']);
    }
  }

}
