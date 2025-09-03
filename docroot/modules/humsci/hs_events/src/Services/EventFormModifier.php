<?php

namespace Drupal\hs_events\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for modifying event forms and handling form auto-unpublish logic.
 */
class EventFormModifier {

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
   * Field name for auto-unpublish setting.
   */
  const AUTO_UNPUBLISH_FIELD = 'field_auto_unpublish';

  /**
   * Config page ID for site options.
   */
  const SITE_OPTIONS_CONFIG_ID = 'hs_site_options';

  /**
   * Field name for site auto-unpublish setting.
   */
  const SITE_AUTO_UNPUBLISH_FIELD = 'field_site_auto_unpublish';

  /**
   * Constructs a new EventFormModifier service.
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
