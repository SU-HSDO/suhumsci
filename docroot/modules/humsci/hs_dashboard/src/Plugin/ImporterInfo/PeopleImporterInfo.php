<?php

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
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
      $container->get('config.factory')
    );
  }

  function getTableHeaders(): array {
    return [
      $this->t('Importer (migration) name'),
      $this->t('Org Code and Workgroup'),
    ];
  }

  function getTableRows(): array {
    $capx_importers = $this->entityTypeManager->getStorage('capx_importer')->loadMultiple();
    $table_rows = [];

    foreach ($capx_importers as $importer) {
      /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
      $table_rows[] = [
        'data' => [
          ['data' => $importer->label()],
          ['data' => $importer->getWorkgroups(TRUE)],
        ],
      ];
    }
    return $table_rows;
  }

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
    return $this->t('<em>There are no people importers configured.</em>');
  }

}
