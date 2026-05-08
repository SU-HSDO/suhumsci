<?php

namespace Drupal\hs_migrate\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for hs_migrate configuration.
 *
 * @package Drupal\hs_migrate\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [ConfigEvents::IMPORT => 'onConfigImport'];
  }

  /**
   * After config import, move D7 importers to a single location.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   Triggered event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $config_page_storage = $this->entityTypeManager->getStorage('config_pages');
    if ($config_page_storage->load('drupal_7_importers')) {
      return;
    }
    $urls = [
      'field_news_xml' => $this->getConfigPageValue('hs_migrate_news', 'field_news_xml_feed'),
    ];

    $urls = array_filter($urls);
    if ($urls) {
      $urls['type'] = 'drupal_7_importers';
      $urls['context'] = serialize([]);
      $config_page_storage->create($urls)->save();
    }

    $field_storage = $this->entityTypeManager->getStorage('field_config');
    $fields = [
      'config_pages.hs_migrate_news.field_news_xml_feed',
    ];
    foreach ($field_storage->loadMultiple($fields) as $field) {
      $field->delete();
    }

    $page_storage = $this->entityTypeManager->getStorage('config_pages_type');
    $pages = ['hs_migrate_news'];
    foreach ($page_storage->loadMultiple($pages) as $page) {
      $page->delete();
    }
  }

  /**
   * Get a string value from the config page.
   *
   * @param string $page_id
   *   Config page machine name.
   * @param string $field_name
   *   Field machine name.
   *
   * @return mixed
   *   Field values from the old config page.
   */
  protected function getConfigPageValue($page_id, $field_name) {
    $config_page_storage = $this->entityTypeManager->getStorage('config_pages');
    if ($page = $config_page_storage->load($page_id)) {
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $page */
      $value = $page->get($field_name)->getValue();
      $page->delete();
      return $value;
    }
  }

}
