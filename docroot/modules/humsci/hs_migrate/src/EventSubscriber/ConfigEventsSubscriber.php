<?php

namespace Drupal\hs_migrate\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventsSubscriber.
 *
 * @package Drupal\hs_migrate\EventSubscriber
 */
class ConfigEventsSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
      'field_people_xml' => $this->getConfigPageValue('d7_people', 'field_d7_people_xml_url'),
      'field_publications_xml' => $this->getConfigPageValue('publications', 'field_xml_url'),
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
      'config_pages.d7_people.field_d7_people_xml_url',
      'config_pages.publications.field_xml_url',
    ];
    foreach ($field_storage->loadMultiple($fields) as $field) {
      $field_storage->delete();
    }

    $page_storage = $this->entityTypeManager->getStorage('config_pages_type');
    $pages = ['hs_migrate_news', 'd7_people', 'publications'];
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
   * @return string|null
   */
  protected function getConfigPageValue($page_id, $field_name) {
    $config_page_storage = $this->entityTypeManager->getStorage('config_pages');
    if ($page = $config_page_storage->load($page_id)) {
      $value = $page->get($field_name)->getValue();
      $page->delete();
      return $value;
    }
  }

}