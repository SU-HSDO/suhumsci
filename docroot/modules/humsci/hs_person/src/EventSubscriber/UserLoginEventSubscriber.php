<?php

namespace Drupal\hs_person\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new UserLoginEventSubscriber object.
   *
   * @param \Drupal\hs_person\Service\PersonAuthorship $person_authorship
   *   The person authorship service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(PersonAuthorship $person_authorship, LoggerChannelFactoryInterface $logger_factory) {
    $this->personAuthorship = $person_authorship;
    $this->loggerFactory = $logger_factory;
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

    try {
      $this->personAuthorship->processPersonAuthorship($account);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_person')->error('Error processing person authorship for user @username: @message', [
        '@username' => $account->getAccountName(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
