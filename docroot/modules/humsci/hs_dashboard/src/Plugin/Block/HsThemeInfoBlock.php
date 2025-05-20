<?php

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\hs_dashboard\AnimationStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Theme and Color Palette' Block.
 *
 * @Block(
 *   id = "hs_theme_info_block",
 *   admin_label = @Translation("Theme and Color Palette"),
 * )
 */
class HsThemeInfoBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs a new HsThemeInfoBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeManagerInterface $theme_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $name = \Drupal::config('system.theme')->get('default');
    $colors = theme_get_setting('theme_color_pairing', $name) ?: 'not set';

    return [
      '#theme' => 'hs_theme_info_block',
      '#theme_name' => $name,
      '#color_pairing' => $colors,
      '#animation_enhancements' => AnimationStatus::fromTheme($name)->value,
      '#help_text' => $this->t('Contact H&S Web for changes.'),
    ];
  }

}
