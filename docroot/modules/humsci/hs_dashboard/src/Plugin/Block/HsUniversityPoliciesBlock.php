<?php

namespace Drupal\hs_dashboard\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'University Policies' Block.
 *
 * @Block(
 *   id = "hs_university_policies_block",
 *   admin_label = @Translation("University Policies"),
 * )
 */
class HsUniversityPoliciesBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Define policies with links.
    $policies = [
      'Stanford web standards (MinWeb)' => 'https://minweb.stanford.edu/',
      'Accessibility' => 'https://soda.stanford.edu/',
      'Images, video and copyright' => 'https://guides.library.stanford.edu/c.php?g=1326917&p=9846465',
      'Privacy' => 'https://minpriv.stanford.edu/',
      'Using AI in content' => 'https://ucomm.stanford.edu/policies/ai-guidelines-for-marketing-and-communications/',
      'Brand and trademarks' => 'https://identity.stanford.edu/',
      'All Stanford digital policies' => 'https://communicators.stanford.edu/policies/digital-policies/',
    ];

    // Generate list of links.
    $items = [];
    foreach ($policies as $title => $url) {
      $items[] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromUri($url, ['attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#prefix' => '<p>' . $this->t('Everything on your site must follow Stanford policy. Use these when you plan content or get a request that may not fit.') . '</p>',
    ];
  }

}
