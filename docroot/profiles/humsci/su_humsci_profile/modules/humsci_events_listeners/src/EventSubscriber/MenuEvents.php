<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Menu & menu link event subscriber.
 */
class MenuEvents implements EventSubscriberInterface {

  use HsEventListenersTrait;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [
      EntityHookEvents::ENTITY_PRE_SAVE => 'entityPresave',
    ];
    return $events;
  }

  /**
   * Adjust the menu link content entity on save.
   *
   * @param EntityPresaveEvent $event
   *   Triggered Event.
   */
  public function entityPresave(EntityPresaveEvent $event): void {
    if ($event->getEntity()->getEntityTypeId() == 'menu_link_content') {
      self::preSaveMenuLinkContent($event->getEntity());
    }
  }


  /**
   * Before saving a menu item, adjust the path if an internal path exists.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $entity
   *   The menu link being saved.
   */
  protected static function preSaveMenuLinkContent(MenuLinkContentInterface $entity): void {
    $cache_tags = [];

    $destination = $entity->get('link')->getString();
    if ($internal_path = self::lookupInternalPath($destination)) {
      $entity->set('link', $internal_path);
    }

    // For new menu link items created on a node form (normally), set the
    // expanded attribute so all menu items are expanded by default.
    $expanded = $entity->isNew() ?: (bool) $entity->get('expanded')->getString();
    $entity->set('expanded', $expanded);

    // When a menu item is added as a child of another menu item clear the
    // parent pages cache so that the block shows up as it doesn't get
    // invalidated just by the menu cache tags.
    while ($entity && ($parent_id = $entity->getParentId())) {

      [$entity_name, $uuid] = explode(':', $parent_id);
      $entity = \Drupal::entityTypeManager()
        ->getStorage($entity_name)
        ->loadByProperties(['uuid' => $uuid]);

      if (!$entity) {
        break;
      }

      $entity = array_pop($entity);
      /** @var \Drupal\Core\Url $url */
      $url = $entity->getUrlObject();
      if (!$url->isExternal() && $url->isRouted()) {
        $params = $url->getRouteParameters();
        if (isset($params['node'])) {
          $cache_tags[] = 'node:' . $params['node'];
        }
      }
    }
    Cache::invalidateTags($cache_tags);
  }

}
