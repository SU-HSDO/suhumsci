<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user_event_dispatcher\Event\User\UserCancelMethodsAlterEvent;
use Drupal\user_event_dispatcher\UserHookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for user listeners.
 */
class UserEvents implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserHookEvents::USER_CANCEL_METHODS_ALTER => ['alterCancelMethods'],
    ];
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(protected AccountProxyInterface $currentUser) {
  }

  /**
   * Alter the user cancel methods to prevent deleting users.
   *
   * @param \Drupal\user_event_dispatcher\Event\User\UserCancelMethodsAlterEvent $event
   *   Triggered event.
   */
  public function alterCancelMethods(UserCancelMethodsAlterEvent $event): void {
    $methods = &$event->getMethods();
    $user_roles = $this->currentUser->getRoles(TRUE);
    $is_admin = in_array('administrator', $user_roles);
    $methods['user_cancel_delete']['access'] = $is_admin;
  }

}
