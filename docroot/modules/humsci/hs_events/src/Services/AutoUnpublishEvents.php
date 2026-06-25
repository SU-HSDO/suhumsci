<?php

namespace Drupal\hs_events\Services;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for auto-unpublishing past events.
 */
class AutoUnpublishEvents {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Node type for events.
   */
  const EVENT_NODE_TYPE = 'hs_event';

  /**
   * Field name for auto-unpublish setting.
   */
  const AUTO_UNPUBLISH_FIELD = 'field_auto_unpublish';

  /**
   * Field name for event date.
   */
  const EVENT_DATE_FIELD = 'field_hs_event_date';

  /**
   * Constructs a new AutoUnpublishEvents service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Unpublishes past events that have auto-unpublish enabled.
   *
   * @param int $limit
   *   Maximum number of events to process. Defaults to 100.
   *
   * @return int
   *   The number of events that were unpublished.
   */
  public function unpublishPastEvents(int $limit = 100): int {
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Get current time in UTC to match database storage.
    $current_time = new DrupalDateTime('now', 'UTC');
    $current_timestamp = $current_time->getTimestamp();

    // Query for published hs_event nodes that:
    // 1. Are published (status = 1).
    // 2. Have field_auto_unpublish = 1.
    // 3. Have field_hs_event_date with end time in the past.
    $query = $node_storage->getQuery()
      ->condition('type', self::EVENT_NODE_TYPE)
      ->condition('status', 1)
      ->condition(self::AUTO_UNPUBLISH_FIELD, 1)
      ->condition(self::EVENT_DATE_FIELD . '.end_value', $current_timestamp, '<')
      ->range(0, $limit)
      ->accessCheck(FALSE);

    $nids = $query->execute();

    if (empty($nids)) {
      return 0;
    }

    $nodes = $node_storage->loadMultiple($nids);
    $unpublished_count = 0;
    $logger = $this->loggerFactory->get('hs_events');

    foreach ($nodes as $node) {
      try {
        $node->setUnpublished();
        $node->save();
        $unpublished_count++;

        // Log the action for debugging purposes.
        $logger->info('Auto-unpublished event "@title" (ID: @id) as it is past its end date.', [
          '@title' => $node->getTitle(),
          '@id' => $node->id(),
        ]);
      }
      catch (\Exception $e) {
        $logger->error('Failed to unpublish event "@title" (ID: @id): @error', [
          '@title' => $node->getTitle(),
          '@id' => $node->id(),
          '@error' => $e->getMessage(),
        ]);
      }
    }

    if ($unpublished_count > 0) {
      $logger->info('Auto-unpublished @count past events.', [
        '@count' => $unpublished_count,
      ]);
    }

    return $unpublished_count;
  }

}
