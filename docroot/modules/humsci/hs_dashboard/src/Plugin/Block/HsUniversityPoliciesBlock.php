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
   * Constructs a new HsUniversityPoliciesBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing plugin settings.
   * @param string $plugin_id
   *   The plugin ID for the block.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
      'University Trademark and Images' => 'https://adminguide.stanford.edu/chapter-1/subchapter-5/policy-1-5-4',
      'Copyright' => 'https://uit.stanford.edu/security/copyright-infringement',
      'Online Privacy' => 'https://www.stanford.edu/site/privacy/',
      'Accessibility' => 'https://www.stanford.edu/site/accessibility/',
      'Terms of use for Sites' => 'https://www.stanford.edu/site/terms/',
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
      '#prefix' => '<p>' . $this->t('All site content must comply with the University Policies.') . '</p>',
    ];
  }
}
