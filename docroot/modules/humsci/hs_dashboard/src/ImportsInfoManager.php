<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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
   * Config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPagesLoader;

  /**
   * The widget manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $widgetManager;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages_loader
   *   Config pages loader service.
   * @param \Drupal\Core\Field\WidgetPluginManager $widget_manager
   *  The widget manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    ConfigPagesLoaderServiceInterface $config_pages_loader,
    WidgetPluginManager $widget_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->capxConfig = $config_factory->getEditable('hs_capx.settings');
    $this->configPagesLoader = $config_pages_loader;
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('config_pages.loader'),
      $container->get('plugin.manager.field.widget'),
    );
  }

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
    $config_pages = $this->entityTypeManager->getStorage('config_pages')->load('localist_events');
    $field_definition = $config_pages->getFieldDefinition('field_url_separate');
    $widget = $this->getWidgetInstance('localist_url', $field_definition);

    $full_url = $config_pages->get('field_url_separate')->first()->getValue()['uri'];
    $parsed_url = parse_url($full_url);
    $base_url = $parsed_url['scheme'] . "://" . $parsed_url['host'] . "/";

    $localist_api_data = $widget->getApiData($base_url);

    $dept_groups = [];
    foreach ($localist_api_data['departments'] as $department) {
      $dept_groups[$department['department']['id']] = $department['department']['name'];
    }

    foreach ($localist_api_data['groups'] as $group) {
      $dept_groups[$group['group']['id']] = $group['group']['name'];
    }

    $places = [];
    foreach ($localist_api_data['places'] as $place) {
      $places[$place['place']['id']] = $place['place']['name'];
    }

    $localist_urls = $config_pages->get('field_url_separate')->getValue();

    foreach ($localist_urls as $url) {
      parse_str(parse_url(urldecode($url['uri']), PHP_URL_QUERY), $query_parameters);
      $filters = [
        $dept_groups[$query_parameters['group_id']],
        isset($query_parameters['venue_id']) ? $places[$query_parameters['venue_id']] : NULL,
      ];

      $table_rows[] = [
        'data' => [
          ['data' => implode(', ', $filters)],
        ],
      ];
    }

    return [
      '#theme' => 'table',
      '#caption' => $this->t('Events Importers'),
      '#header' => [
        ['data' => $this->t('Filters')],
        ['data' => $this->t('Recurring event treatment')],
      ],
      '#rows' => $table_rows,
    ];
  }

}
