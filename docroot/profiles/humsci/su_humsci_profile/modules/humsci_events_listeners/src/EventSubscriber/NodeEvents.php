<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent;
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
   * On node save, perform some actions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   Triggered Event.
   */
  public function entityPresave(EntityPresaveEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  /**
   * On node delete, perform some actions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityDeleteEvent $event
   *   Triggered Event.
   */
  public function entityDelete(EntityDeleteEvent $event) {
    self::flushEntityCaches($event->getEntity());
  }

  /**
   * Flush extra caches for a node action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  protected static function flushEntityCaches(EntityInterface $entity) {
    if ($entity instanceof NodeInterface) {
      Cache::invalidateTags(['config:search_api.index.default_index']);
    }
  }

}
