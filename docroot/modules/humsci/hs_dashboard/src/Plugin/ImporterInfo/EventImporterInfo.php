<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hs_dashboard\Plugin\ImporterInfoBase;
use Drupal\hs_dashboard\Plugin\ImporterInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event importer info.
 *
 * @ImporterInfo(
 *   id = "event_importer_info",
 *   label = @Translation("Event Importers"),
 *   description = @Translation("Retrieves event importer information from Localist."),
 *   weight = 20,
 * )
 */
class EventImporterInfo extends ImporterInfoBase implements ImporterInfoInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
  protected $eventTableRows;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value_factory
   *   The KeyValue factory interface.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The DateFormatter.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
   *   The widget manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value_factory,
    DateFormatterInterface $date_formatter,
    WidgetPluginManager $widget_manager,
    EntityFieldManagerInterface $entity_field_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $key_value_factory, $date_formatter);
    $this->entityTypeManager = $entity_type_manager;
    $this->widgetManager = $widget_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->localistConfigPages = $this->entityTypeManager->getStorage('config_pages')->load('localist_events');
    $this->eventTableRows = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('date.formatter'),
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
   * {@inheritdoc}
   */
  public function getTableHeaders(): array {
    return [
      $this->t('Departments/Groups'),
      $this->t('Last Imported'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTableRows(): array {

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

    return $this->eventTableRows;
  }

  /**
   * {@inheritDoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    return $this->t('<em>There are no Stanford Event importers configured.</em>');
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
    if (!$this->localistConfigPages->get($field_name)->getValue()) {
      return [];
    }

    $base_url = $this->getLocalistBaseUrl($field_name);
    if (!$base_url) {
      return [];
    }

    $localist_api_data = $this->fetchLocalistApiData($field_name, $base_url);

    return [
      'dept_groups' => $this->processDepartmentsAndGroups($localist_api_data),
      'places' => $this->processPlaces($localist_api_data),
      'filters' => $this->processFilters($localist_api_data),
    ];

  }

  /**
   * Gets the base URL for Localist API from the field.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return string|null
   *   The base URL or NULL if it can't be determined.
   */
  private function getLocalistBaseUrl($field_name) {
    $full_url = $this->localistConfigPages->get($field_name)->first()->getValue()['uri'] ?? NULL;
    if (!$full_url) {
      return NULL;
    }

    $parsed_url = parse_url($full_url);
    return isset($parsed_url['scheme'], $parsed_url['host'])
      ? $parsed_url['scheme'] . "://" . $parsed_url['host'] . "/"
      : NULL;
  }

  /**
   * Fetches the Localist API data.
   *
   * @param string $field_name
   *   The field name.
   * @param string $base_url
   *   The base URL.
   *
   * @return array
   *   The Localist API data.
   */
  private function fetchLocalistApiData($field_name, $base_url) {
    $field_definition = $this->localistConfigPages->getFieldDefinition($field_name);
    $widget = $this->getWidgetInstance('localist_url', $field_definition);

    $field_definition = $this->localistConfigPages->getFieldDefinition($field_name);
    $widget = $this->getWidgetInstance('localist_url', $field_definition);
    return $widget->getApiData($base_url) ?? [];
  }

  /**
   * Processes departments and groups into a lookup table.
   *
   * @param array $api_data
   *   The Localist API data.
   *
   * @return array
   *   The department and group lookup table.
   */
  private function processDepartmentsAndGroups(array $api_data) {
    $dept_groups = [];
    foreach (['departments' => 'department', 'groups' => 'group'] as $key => $subkey) {
      if (!empty($api_data[$key])) {
        foreach ($api_data[$key] as $item) {
          if (isset($item[$subkey]['id'], $item[$subkey]['name'])) {
            $dept_groups[$item[$subkey]['id']] = $item[$subkey]['name'];
          }
        }
      }
    }
    return $dept_groups;
  }

  /**
   * Processes places into a lookup table.
   *
   * @param array $api_data
   *   The Localist API data.
   *
   * @return array
   *   The places lookup table.
   */
  private function processPlaces(array $api_data) {
    $places = [];
    if (!empty($api_data['places'])) {
      foreach ($api_data['places'] as $place) {
        if (isset($place['place']['id'], $place['place']['name'])) {
          $places[$place['place']['id']] = $place['place']['name'];
        }
      }
    }
    return $places;
  }

  /**
   * Processes filters (audience, subject, types) into a lookup table.
   *
   * @param array $api_data
   *   The Localist API data.
   *
   * @return array
   *   The filters lookup table.
   */
  private function processFilters(array $api_data) {
    $filters = [];
    if (!empty($api_data['events/filters'])) {
      foreach ($api_data['events/filters'] as $filters_group) {
        foreach ($filters_group as $filter) {
          if (isset($filter['id'], $filter['name'])) {
            $filters[$filter['id']] = $filter['name'];
          }
        }
      }
    }
    return $filters;
  }

  /**
   * Generates rendered event filters.
   *
   * Stored URLs have query parameters that are IDs for each filter.
   * We parse each query parameter and then lookup the ID in the localist API
   * data to get the filter name. @see getLocalistLookup() for more info.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return void
   *   No return value.
   */
  private function generateEventFilters($field_name) {
    if (!$this->localistConfigPages) {
      return;
    }
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

      $this->eventTableRows[] = [
        'data' => [
          ['data' => implode(', ', array_filter($filters))],
          ['data' => $this->lastImportTime('hs_localist_scheduled')],
        ],
      ];
    }
  }

  /**
   * Generates rendered event bookmarks.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return void
   *   No return value.
   */
  private function generateEventBookmarks($field_name) {
    if (!$this->localistConfigPages) {
      return;
    }
    $bookmarks = $this->localistConfigPages->get($field_name)->getValue();
    foreach ($bookmarks as $bookmark) {
      $this->eventTableRows[] = [
        'data' => [
          ['data' => $this->t('Bookmark <small>(@url)</small>', ['@url' => $bookmark['uri']])],
        ],
      ];
    }
  }

}
