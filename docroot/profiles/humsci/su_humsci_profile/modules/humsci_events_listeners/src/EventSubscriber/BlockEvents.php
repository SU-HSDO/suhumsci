<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\core_event_dispatcher\BlockHookEvents;
use Drupal\core_event_dispatcher\Event\Block\BlockAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for block entities.
 */
class BlockEvents implements EventSubscriberInterface {

  /**
   * Current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      BlockHookEvents::BLOCK_ACCESS => 'blockAccess',
    ];
  }

  /**
   * Event subscriber listener.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack service object.
   */
  public function __construct(RequestStack $requestStack) {
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * Block access event subscriber.
   *
   * @param \Drupal\core_event_dispatcher\Event\Block\BlockAccessEvent $event
   *   Triggered event.
   */
  public function blockAccess(BlockAccessEvent $event) {
    // Disable the page title block on 404 page IF the page is a node. Nodes
    // should have the page title displayed in the node display configuration so
    // we can rely on that.
    if (
      $event->getBlock()->getPluginId() == 'page_title_block' &&
      $this->currentRequest->query->get('_exception_statuscode') == 404
    ) {
      $access = AccessResult::forbiddenIf($this->currentRequest->attributes->get('node'))
        ->addCacheableDependency($event->getBlock());
      $event->addAccessResult($access);
    }
  }

}
