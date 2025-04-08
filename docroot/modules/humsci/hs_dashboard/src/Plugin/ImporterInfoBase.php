<?php

namespace Drupal\hs_dashboard\Plugin;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Importer Info plugins.
 */
abstract class ImporterInfoBase extends PluginBase implements ImporterInfoInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * KeyValueStore used to track the import times of each migration.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected KeyValueStoreInterface $lastImportedStore;

  /**
   * Date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value_factory,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->lastImportedStore = $key_value_factory->get('migrate_last_imported');
    $this->dateFormatter = $date_formatter;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTableHeaders(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTableRows(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTableSuffix(): ?TranslatableMarkup {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCaption(): TranslatableMarkup {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    return $this->t('<em>No import data available.</em>');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->getPluginDefinition()['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function showImporter(): bool {
    return TRUE;
  }

  /**
   * When was this importer last run.
   *
   * @param string $migration_id
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected function lastImportTime(string $migration_id) {
    if ($last_imported = $this->lastImportedStore->get($migration_id, FALSE)) {
      return $this->dateFormatter->format($last_imported / 1000, 'short');
    }
    return $this->t('Unknown');
  }

}
