<?php

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
      '#theme_name' => $this->getThemeName($name),
      '#color_pairing' => ucfirst($colors),
      '#animation_enhancements' => AnimationStatus::fromTheme($name)->value,
      '#help_text' => $this->t('Visit the <a href=":colorful" target="_blank">Colorful theme reference website</a> or the <a href=":traditional" target="_blank">Traditional theme reference website</a> for inspiration and to see examples of the different color pairing options.', [
        ':colorful' => 'https://hsweb-referencecolorful.stanford.edu/',
        ':traditional' => 'https://hsweb-referencetraditional.stanford.edu/',
      ]),
    ];
  }

  /**
   * Returns the user friendly name of the theme from its machine name.
   *
   * @param string $machine_name
   *   The machine name of the theme.
   *
   * @return string
   */
  protected function getThemeName($machine_name) {
    $themes = [
      'humsci_colorful' => 'Colorful',
      'humsci_traditional' => 'Traditional',
    ];
    return $themes[$machine_name] ?? $machine_name;
  }

}
