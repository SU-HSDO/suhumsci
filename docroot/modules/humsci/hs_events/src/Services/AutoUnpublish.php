<?php

namespace Drupal\hs_events\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for auto-unpublishing past events and handling form modifications.
 */
class AutoUnpublish {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Config page ID for site options.
   */
  const SITE_OPTIONS_CONFIG_ID = 'hs_site_options';

  /**
   * Field name for site auto-unpublish setting.
   */
  const SITE_AUTO_UNPUBLISH_FIELD = 'field_site_auto_unpublish';

  /**
   * Constructs a new AutoUnpublish service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
  }

  /**
   * Unpublishes past events that have auto-unpublish enabled.
   *
   * @return int
   *   The number of events that were unpublished.
   */
  public function unpublishPastEvents(): int {
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
        $logger->notice('Auto-unpublished event "@title" (ID: @id) as it is past its end date.', [
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
      $logger->info('Auto-unpublished @count past events during cron run.', [
        '@count' => $unpublished_count,
      ]);
    }

    return $unpublished_count;
  }

  /**
   * Checks if the site auto-unpublish setting is enabled.
   *
   * @return bool
   *   TRUE if the site setting is enabled, FALSE otherwise.
   */
  public function isSiteAutoUnpublishEnabled(): bool {
    try {
      $site_options = $this->entityTypeManager
        ->getStorage('config_pages')
        ->load(self::SITE_OPTIONS_CONFIG_ID);

      if (!$site_options) {
        return FALSE;
      }

      $field_value = $site_options->get(self::SITE_AUTO_UNPUBLISH_FIELD);
      return $field_value && $field_value->value == '1';
    }
    catch (\Exception $e) {
      // Log the error but don't break the form.
      $this->loggerFactory->get('hs_events')->error('Error checking site auto-unpublish setting: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Sets the default value for the auto-unpublish field based on site setting.
   *
   * @param array &$form
   *   The form array to modify.
   */
  public function setAutoUnpublishDefaultValue(array &$form): void {
    // Check if the auto-unpublish field exists in the form.
    if (!isset($form[self::AUTO_UNPUBLISH_FIELD]['widget']['value'])) {
      return;
    }

    // Set the default value based on site configuration.
    if ($this->isSiteAutoUnpublishEnabled()) {
      $form[self::AUTO_UNPUBLISH_FIELD]['widget']['value']['#default_value'] = 1;
    }
  }

  /**
   * Moves the auto-unpublish field to the options group.
   *
   * @param array &$form
   *   The form array to modify.
   */
  public function moveAutoUnpublishFieldToOptions(array &$form): void {
    if (isset($form[self::AUTO_UNPUBLISH_FIELD])) {
      $form[self::AUTO_UNPUBLISH_FIELD]['#group'] = 'options';
    }
  }

}
