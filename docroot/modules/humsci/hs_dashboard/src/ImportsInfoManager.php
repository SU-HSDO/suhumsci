<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\stanford_migrate\EventSubscriber\EventsSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to handle Import information for block tables.
 */
class ImportsInfoManager implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $capxConfig;

  /**
   * The widget manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $widgetManager;

  /**
   * Localist config pages entity.
   *
   * @var \Drupal\config_pages\Entity\ConfigPages
   */
  protected $localistConfigPages;

  /**
   * Event table rows.
   *
   * @var array
   */
  protected $eventImporterTableRows;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
   *   The widget manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    WidgetPluginManager $widget_manager,
    EntityFieldManagerInterface $entity_field_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->capxConfig = $config_factory->getEditable('hs_capx.settings');
    $this->widgetManager = $widget_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->localistConfigPages = $this->entityTypeManager->getStorage('config_pages')->load('localist_events');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.widget'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * Returns a field widget instance.
   */
  public function getWidgetInstance($plugin_id, $field_definition, $settings = [], $third_party_settings = []) {
    return $this->widgetManager->createInstance($plugin_id, [
      'field_definition' => $field_definition,
      'settings' => $settings,
      'third_party_settings' => $third_party_settings,
    ]);
  }

  /**
   * Generates a table with people import information.
   */
  public function generatePeopleTable(): array {
    $capx_importers = $this->entityTypeManager->getStorage('capx_importer')->loadMultiple();
    if (!$capx_importers) {
      return [
        '#theme' => 'table',
        '#caption' => $this->t('<p>People Importers</p><em>There are no people importers configured.</em>'),
      ];
    }

    $table_rows = [];
    $orphan_action = $this->t('Do nothing');

    if ($orphan_setting = $this->capxConfig->get('orphan_action')) {
      $orphan_labels = [
        EventsSubscriber::ORPHAN_DELETE => $this->t('Delete'),
        EventsSubscriber::ORPHAN_UNPUBLISH => $this->t('Unpublish'),
      ];

      $orphan_action = $orphan_labels[$orphan_setting];
    }

    foreach ($capx_importers as $importer) {
      /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
      $table_rows[] = [
        'data' => [
          ['data' => $importer->label()],
          ['data' => $importer->getWorkgroups(TRUE)],
        ],
      ];
    }

    return [
      '#theme' => 'table',
      '#caption' => $this->t('People Importers'),
      '#header' => [
        ['data' => $this->t('Importer (migration) name')],
        ['data' => $this->t('Org Code and Workgroup')],
      ],
      '#rows' => $table_rows,
      '#suffix' => $this->t(
        '<p>People importer orphan action: @action</p>',
        ['@action' => $orphan_action]
      ),
    ];
  }

  /**
   * Generates a table with event import information.
   */
  public function generateEventTable(): array {

    $filters_to_create = [
      'field_url_individ' => 'filter',
      'field_url_book_i' => 'bookmark',
      'field_url_separate' => 'filter',
      'field_url_book_s' => 'bookmark',
    ];

    foreach ($filters_to_create as $field_name => $type) {
      switch ($type) {
        case 'filter':
          $this->generateEventFilters($field_name);
          break;

        case 'bookmark':
          $this->generateEventBookmarks($field_name);

          break;
      }
    }

    return [
      '#theme' => 'table',
      '#caption' => $this->t('Events Importers'),
      '#header' => [
        ['data' => $this->t('Filters')],
        ['data' => $this->t('Recurring event treatment')],
      ],
      '#rows' => $this->eventImporterTableRows,
    ];
  }

  /**
   * Gets the localist lookup data from the localist API.
   *
   * This method piggybacks off of a custom localist widget in the
   * stanford_fields module. That widget contains a way to get Localist API
   * data and cache it for future calls.
   *
   * Once the API data is obtained, a lookup table/array of IDs and filter names
   * is generated. This allows the generateEventFilters() function to more
   * easily get a filter name to display in the table.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The localist lookup data by key.
   */
  private function getLocalistLookup($field_name) {
    /* We can't seem to get the widget base URL from settings. So, we grab the
     * first URL and parse it as the base URL. Since the custom widget does not
     * allow users to edit the URLs, this is a fair assumption that all URLs
     * have the same base URL.
     */
    $full_url = $this->localistConfigPages->get($field_name)->first()->getValue()['uri'];
    $parsed_url = parse_url($full_url);
    $base_url = $parsed_url['scheme'] . "://" . $parsed_url['host'] . "/";

    $field_definition = $this->localistConfigPages->getFieldDefinition($field_name);
    $widget = $this->getWidgetInstance('localist_url', $field_definition);
    $localist_api_data = $widget->getApiData($base_url);

    $localist_lookup = [
      'dept_groups' => [],
      'places' => [],
      'filters' => [],
    ];

    // Combines departments and groups into the same lookup table.
    foreach (['departments' => 'department', 'groups' => 'group'] as $key => $subkey) {
      if (!empty($localist_api_data[$key])) {
        foreach ($localist_api_data[$key] as $item) {
          if (isset($item[$subkey]['id'], $item[$subkey]['name'])) {
            $localist_lookup['dept_groups'][$item[$subkey]['id']] = $item[$subkey]['name'];
          }
        }
      }
    }

    // Retrieves places.
    if (!empty($localist_api_data['places'])) {
      foreach ($localist_api_data['places'] as $place) {
        if (isset($place['place']['id'], $place['place']['name'])) {
          $localist_lookup['places'][$place['place']['id']] = $place['place']['name'];
        }
      }
    }

    // Retrieves audience, subject, and types.
    if (!empty($localist_api_data['events/filters'])) {
      foreach ($localist_api_data['events/filters'] as $filters) {
        foreach ($filters as $filter) {
          if (isset($filter['id'], $filter['name'])) {
            $localist_lookup['filters'][$filter['id']] = $filter['name'];
          }
        }
      }
    }

    return $localist_lookup;
  }

  /**
   * Generates event filters.
   *
   * Stored URLs have query parameters that are IDs for each filter.
   * We parse each query parameter and then lookup the ID in the localist API
   * data to get the filter name. @see getLocalistLookup() for more info.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return void
   */
  private function generateEventFilters($field_name) {
    $localist_lookup = $this->getLocalistLookup($field_name);
    $localist_urls = $this->localistConfigPages->get($field_name)->getValue();
    foreach ($localist_urls as $url) {
      parse_str(parse_url(urldecode($url['uri']), PHP_URL_QUERY), $query_parameters);

      $types = [];
      if (isset($query_parameters['type'])) {
        foreach ($query_parameters['type'] as $type_id) {
          $types[] = $localist_lookup['filters'][$type_id];
        }
      }

      $filters = [
        $localist_lookup['dept_groups'][$query_parameters['group_id']],
        isset($query_parameters['venue_id']) ? $localist_lookup['places'][$query_parameters['venue_id']] : NULL,
        $types ? implode(', ', $types) : NULL,
      ];

      $this->eventImporterTableRows[] = [
        'data' => [
          ['data' => implode(', ', array_filter($filters))],
          ['data' => $this->getFieldLabel($field_name)],
        ],
      ];
    }
  }

  /**
   * Generates event bookmarks.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return void
   */
  private function generateEventBookmarks($field_name) {
    $bookmarks = $this->localistConfigPages->get($field_name)->getValue();
    foreach ($bookmarks as $bookmark) {
      $this->eventImporterTableRows[] = [
        'data' => [
          ['data' => $this->t('Bookmark')],
          ['data' => $this->getFieldLabel($field_name)],
        ],
      ];
    }
  }

  /**
   * Gets the field label from a field name for localist_events config_pages.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string
   *   The field label.
   */
  private function getFieldLabel($field_name) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('config_pages', 'localist_events');
    if (isset($field_definitions[$field_name])) {
      return $field_definitions[$field_name]->getLabel();
    }
    return '';
  }

}
