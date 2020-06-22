<?php

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Par of H&S' block.
 *
 * @Block(
 *  id = "part_of_hs",
 *  admin_label = @Translation("Part of H&S Block"),
 * )
 */
class PartOfHsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public function build() {
    $lock_line_1 = theme_get_setting('lockup_1');
    $lock_line_2 = theme_get_setting('lockup_2');
    $site_name = $this->configFactory->get('system.site')->get('name');

    $url = Url::fromUri('https://humsci.stanford.edu/');
    $link = Link::fromTextAndUrl($this->t('School of Humanities and Sciences'), $url);
    if ($lock_line_1 && $lock_line_2) {
      $markup = $this->t('The @lock1 @lock2 is part of the @link.', [
        '@lock1' => $lock_line_1,
        '@lock2' => $lock_line_2,
        '@link' => $link->toString(),
      ]);
    }
    else {
      $markup = $this->t('The @sitename is part of the @link.', ['@sitename' => $site_name, '@link' => $link->toString(),]);
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $markup,
    ];
  }

}
