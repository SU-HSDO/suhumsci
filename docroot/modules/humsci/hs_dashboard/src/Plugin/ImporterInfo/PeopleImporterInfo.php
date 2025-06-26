<?php

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hs_dashboard\Plugin\ImporterInfoBase;
use Drupal\hs_dashboard\Plugin\ImporterInfoInterface;
use Drupal\stanford_migrate\EventSubscriber\EventsSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * People importer info.
 *
 * @ImporterInfo(
 *   id = "people_importer_info",
 *   label = @Translation("People Importers"),
 *   description = @Translation("Retrieves people importer information from capx_importer entities."),
 *   weight = 30,
 * )
 */
class PeopleImporterInfo extends ImporterInfoBase implements ImporterInfoInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $capxConfig;

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
   *   The KeyValue factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The DateFormatter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value_factory,
    DateFormatterInterface $date_formatter,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $key_value_factory, $date_formatter);
    $this->capxConfig = $config_factory->getEditable('hs_capx.settings');
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
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getTableHeaders(): array {
    return [
      $this->t('Importer (migration) name'),
      $this->t('Org Code or Workgroup'),
      $this->t('Last Imported'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getTableRows(): array {
    $storage = $this->entityTypeManager->getStorage('capx_importer');
    $query = $storage->getQuery();
    $query->sort('label');
    $capx_importers = $storage->loadMultiple($query->execute());

    $table_rows = [];

    /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
    foreach ($capx_importers as $importer) {
      // In theory, an importer will only use organizations, or workgroups, but
      // just in case, we support both.
      $orgs_and_workgroups = array_filter([
        $importer->getOrganizations(TRUE),
        $importer->getWorkgroups(TRUE),
      ]);
      $table_rows[] = [
        'data' => [
          ['data' => $importer->label()],
          ['data' => implode(', ', $orgs_and_workgroups)],
          ['data' => $this->lastImportTime('hs_capx')],
        ],
      ];
    }
    return $table_rows;
  }

  /**
   * {@inheritDoc}
   */
  public function getTableSuffix(): TranslatableMarkup {
    $orphan_action = $this->t('Do nothing');

    if ($orphan_setting = $this->capxConfig->get('orphan_action')) {
      $orphan_labels = [
        EventsSubscriber::ORPHAN_DELETE => $this->t('Delete'),
        EventsSubscriber::ORPHAN_UNPUBLISH => $this->t('Unpublish'),
      ];

      $orphan_action = $orphan_labels[$orphan_setting];
    }

    return $this->t(
      '<p>People importer orphan action: @action</p>',
        ['@action' => $orphan_action]
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    return $this->t('There are no Stanford Profiles importers configured.');
  }

}
