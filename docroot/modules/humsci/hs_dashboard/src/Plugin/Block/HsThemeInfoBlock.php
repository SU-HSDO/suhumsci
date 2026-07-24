<?php

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The theme settings provider.
   *
   * @var \Drupal\Core\Extension\ThemeSettingsProvider
   */
  protected $themeSettings;

  /**
   * Constructs a new HsThemeInfoBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ThemeSettingsProvider $theme_settings
   *   The theme settings provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, ThemeSettingsProvider $theme_settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->themeSettings = $theme_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get(ThemeSettingsProvider::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $name = $this->configFactory->get('system.theme')->get('default');
    $colors = $this->themeSettings->getSetting('theme_color_pairing', $name) ?: 'not set';

    return [
      '#theme' => 'hs_theme_info_block',
      '#theme_name' => $this->getThemeName($name),
      '#color_pairing' => ucfirst($colors),
      '#animation_enhancements' => AnimationStatus::fromSetting($this->themeSettings->getSetting('animation_toggle', $name))->value,
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
