<?php

namespace Drupal\su_humsci_profile\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class HumsciEventSubscriber.
 *
 * @package Drupal\su_humsci_profile\EventSubscriber
 */
class HumsciEventSubscriber implements EventSubscriberInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HumsciEventSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [DefaultContentEvents::IMPORT => 'onContentImport'];
  }

  /**
   * After content is imported, act upon it.
   *
   * @param \Drupal\default_content\Event\ImportEvent $event
   *   Event action.
   */
  public function onContentImport(ImportEvent $event) {
    foreach ($event->getImportedEntities() as $entity) {
      if ($entity->getEntityTypeId() == 'node' && $entity->uuid() == '287db095-35b1-4050-8d26-5d8332eeb6a6') {
        $this->configFactory->getEditable('system.site')
          ->set('page.front', '/node/' . $entity->id())
          ->save();
      }
    }
  }

}
