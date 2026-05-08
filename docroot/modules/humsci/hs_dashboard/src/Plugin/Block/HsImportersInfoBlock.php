<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hs_dashboard\Plugin\ImporterInfoManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an importer informational block.
 *
 * @Block(
 *   id = "hs_dashboard_importers",
 *   admin_label = @Translation("Importers"),
 *   category = @Translation("H&amp;S Blocks"),
 * )
 */
class HsImportersInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The imports info manager.
   *
   * @var \Drupal\hs_dashboard\Plugin\ImporterInfoManager
   */
  protected $importerInfoManager;

  /**
   * Constructs a new InlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\hs_dashboard\Plugin\ImporterInfoManager $importer_info_manager
   *   The imports info manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImporterInfoManager $importer_info_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->importerInfoManager = $importer_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hs_dashboard.importer_info_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#theme' => 'hs_importers_info',
      '#importers' => $this->importerInfoManager->generateTables(),
    ];
    return $build;
  }

}
