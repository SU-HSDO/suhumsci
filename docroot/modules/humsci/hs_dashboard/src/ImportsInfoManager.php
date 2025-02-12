<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
  protected $capExConfig;

  /**
   * Constructs a new ViewsBasicManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory interface.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->capExConfig = $config_factory->getEditable('hs_capx.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Generates a table with people import information.
   */
  public function generatePeopleTable(): array {
    $capx_importers = $this->entityTypeManager->getStorage('capx_importer')->loadMultiple();
    if (!$capx_importers) {
      return [];
    }

    $tableRows = [];

    $orphan_labels = [
      EventsSubscriber::ORPHAN_DELETE => $this->t('Delete'),
      EventsSubscriber::ORPHAN_UNPUBLISH => $this->t('Unpublish'),
    ];

    foreach ($capx_importers as $importer) {
      /** @var \Drupal\hs_capx\Entity\CapxImporterInterface $importer */
      $tableRows[] = [
        'data' => [
          ['data' => $importer->label()],
          ['data' => $orphan_labels[$this->capExConfig->get('orphan_action')]],
          ['data' => $importer->getWorkgroups(TRUE)],
        ],
      ];
    }

    return [
      '#theme' => 'table',
      '#caption' => $this->t('People Importers'),
      '#header' => [
        ['data' => $this->t('Importer (migration) name')],
        ['data' => $this->t('Orphan action')],
        ['data' => $this->t('Organization or Workcode')],
      ],
      '#rows' => $tableRows,
    ];
  }

}
