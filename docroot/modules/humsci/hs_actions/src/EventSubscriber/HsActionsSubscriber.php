<?php

namespace Drupal\hs_actions\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * H&S actions event subscriber.
 */
class HsActionsSubscriber implements EventSubscriberInterface {


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_SAVE => 'onPreRowSave',
    ];
  }

  /**
   * If the row item is to be ignored, throw the error to keep it ignored.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   Triggered event from MigrateExecutable.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $id_map = $event->getRow()->getIdMap();
    if ($id_map['source_row_status'] == MigrateIdMapInterface::STATUS_IGNORED) {
      throw new MigrateException('Item is ignored', 0, NULL, MigrationInterface::MESSAGE_INFORMATIONAL, MigrateIdMapInterface::STATUS_IGNORED);
    }
  }

}
