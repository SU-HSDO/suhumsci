<?php

namespace Drupal\hs_config_readonly\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\config_readonly\ReadOnlyFormEvent;

/**
 * Check if the given form should be read-only.
 */
class ConfigReadOnlyEventSubscriber implements EventSubscriberInterface {

  public function onFormAlter(ReadOnlyFormEvent $event) {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReadOnlyFormEvent::NAME][] = ['onFormAlter', 200];
    return $events;
  }

}
