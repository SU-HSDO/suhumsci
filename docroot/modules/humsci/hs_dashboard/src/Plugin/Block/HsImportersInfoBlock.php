<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hs_dashboard\ImportsInfoManager;
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
   * @var \Drupal\hs_dashboard\ImportsInfoManager
   */
  protected $importsInfoManager;

  /**
   * Constructs a new InlineBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\hs_dashboard\ImportsInfoManager $imports_info_manager
   *   The imports info manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImportsInfoManager $imports_info_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->importsInfoManager = $imports_info_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('hs_dashboard.imports_info_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $importers = [];

    $importers[] = $this->importsInfoManager->generatePeopleTable();
    $importers[] = $this->importsInfoManager->generateEventTable();

    $build = [
      '#theme' => 'hs_importers_info',
      '#importers' => $importers,
    ];
    return $build;
  }

}
