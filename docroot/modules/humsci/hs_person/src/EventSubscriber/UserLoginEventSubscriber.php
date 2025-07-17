<?php

namespace Drupal\hs_person\EventSubscriber;

use Drupal\hs_person\Service\PersonAuthorship;
use Drupal\user_event_dispatcher\Event\User\UserLoginEvent;
use Drupal\user_event_dispatcher\UserHookEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for user login events.
 */
class UserLoginEventSubscriber implements EventSubscriberInterface {

  /**
   * The person authorship service.
   *
   * @var \Drupal\hs_person\Service\PersonAuthorship
   */
  protected $personAuthorship;

  /**
   * Constructs a new UserLoginEventSubscriber object.
   *
   * @param \Drupal\hs_person\Service\PersonAuthorship $person_authorship
   *   The person authorship service.
   */
  public function __construct(PersonAuthorship $person_authorship) {
    $this->personAuthorship = $person_authorship;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      UserHookEvents::USER_LOGIN => ['onUserLogin'],
    ];
  }

  /**
   * React to user login events.
   *
   * Delegates to the PersonAuthorship service to handle the business logic
   * of assigning person node ownership to users.
   *
   * @param \Drupal\user_event_dispatcher\Event\User\UserLoginEvent $event
   *   The user login event.
   */
  public function onUserLogin(UserLoginEvent $event) {
    $account = $event->getAccount();
    $this->personAuthorship->processPersonAuthorship($account);
  }

}
