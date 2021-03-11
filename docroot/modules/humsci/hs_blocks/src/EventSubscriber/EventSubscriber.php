<?php

namespace Drupal\hs_blocks\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Connection;

/**
 * Class EventSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EventSubscriber object.
   */
  public function __construct(AccountProxyInterface $currentUser, Connection $database) {
    $this->currentUser = $currentUser;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onKernelRequest'];
    return $events;
  }

  /**
   * If a user is logged in, clear the active trail cache.
   *
   * The rendering cache will still be generated. This is a fix for the menu
   * blocks when an anonymous user views a page, the active trail is cached for
   * that user. For some items, the anonymous user won't have access to and so
   * they active trail will be limited to that user's access. We delete the
   * active trail every time so that it will re-evaluate the user's access for
   * the trail each time. Rendering cache is unaffected by this since those
   * have cache keys and contexts to make them unique based on user's roles.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Triggered Event.
   */
  public function onKernelRequest(RequestEvent $event) {
    if (
      $this->currentUser->isAuthenticated() &&
      $this->database->schema()->tableExists('cache_menu')
    ) {
      // Target only the active trail cache items that are on nodes and are
      // empty.
      $this->database->delete('cache_menu')
        ->condition('cid', 'active-trail:route:entity.node%', 'LIKE')
        ->condition('data', '%menu_link_content%', 'NOT LIKE')
        ->execute();
    }
  }

}
