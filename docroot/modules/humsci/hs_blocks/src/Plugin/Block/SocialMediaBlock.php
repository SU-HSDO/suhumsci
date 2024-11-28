<?php

declare(strict_types=1);

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\multivalue_form_element\Element\MultiValue;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a social media block.
 *
 * @Block(
 *   id = "hs_blocks_social_media_block",
 *   admin_label = @Translation("Social Media Block"),
 *   category = @Translation("H&S Blocks"),
 * )
 */
final class SocialMediaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'icon_size' => 'small',
      'layout' => 'grid',
      'links' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['icon_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon Size'),
      '#options' => [
        'small' => $this->t('Small (32px)'),
        'large' => $this->t('Large (48px)'),
      ],
      '#default_value' => $this->configuration['icon_size'],
      '#required' => TRUE,
    ];

    $form['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => [
        'grid' => $this->t('Grid (no visible label)'),
        'vertical_list' => $this->t('Vertical List (with visible label)'),
      ],
      '#default_value' => $this->configuration['layout'],
      '#required' => TRUE,
    ];

    $form['links'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Links'),
      '#description' => $this->t('Popular social platforms will show their icon, otherwise a generic icon will be shown.'),
      '#cardinality' => MultiValue::CARDINALITY_UNLIMITED,
      '#default_value' => $this->configuration['links'],
      '#element_validate' => [
        [get_class($this), 'validateLinks'],
      ],
      'link_url' => [
        '#type' => 'url',
        '#title' => $this->t('URL'),
        '#description' => $this->t('Social Media Profile URL.'),
      ],
      'link_title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#description' => $this->t('If empty, the social platform name will be used for popular platforms. If the platform is unknown then the domain name will be used.'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['icon_size'] = $form_state->getValue('icon_size');
    $this->configuration['layout'] = $form_state->getValue('layout');

    // Only save links if they have data.
    $links = array_filter($form_state->getValue('links'), function ($link) {
      return !empty($link['link_url']);
    });
    $this->configuration['links'] = $links;

    // This sets the placed block ID to be used for a custom contextual link.
    $this->configuration['placed_block_id'] = $form['id']['#default_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $links = array_map([$this, 'linkWithIcon'], $this->configuration['links']);
    $placed_block_id = $this->configuration['placed_block_id'];

    $build = [
      '#theme' => 'hs_blocks_social_media',
      '#icon_size' => $this->configuration['icon_size'],
      '#layout' => $this->configuration['layout'],
      '#links' => $links,
      '#cache' => [
        'tags' => array_merge($this->getCacheTags(), ['block_view']),
        'contexts' => ['user', 'user.permissions'],
      ],
      '#contextual_links' => [
        'social_media_block' => [
          'route_parameters' => ['block' => $placed_block_id],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Check that links have a URL.
   *
   * @param array $element
   *   The element to check.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateLinks(array $element, FormStateInterface $form_state) {
    $links = $form_state->getValue($element['#parents']);
    foreach ($links as $key => $link) {
      if (!is_array($link)) {
        continue;
      }
      // Test if there is a title but no URL.
      if (!empty($link['link_title']) && empty($link['link_url'])) {
        $form_state->setErrorByName(
          implode('][', array_merge($element['#parents'], [$key, 'link_url'])),
          t('The URL must be provided if a label is set')
        );
      }
    }
  }

  /**
   * Returns a list of social media providers with their url, title and icon.
   *
   * @return array
   *   The list of social media providers.
   */
  protected function getProviders(): array {
    return [
      [
        'domains' => ['facebook.com', 'fb.com', 'fb.me'],
        'icon_classes' => 'fa-brands fa-square-facebook',
        'title' => 'Facebook',
      ],
      [
        'domains' => ['twitter.com', 'x.com'],
        'icon_classes' => 'fa-brands fa-square-x-twitter',
        'title' => 'Twitter',
      ],
      [
        'domains' => ['linkedin.com', 'lnkd.in'],
        'icon_classes' => 'fa-brands fa-linkedin',
        'title' => 'Linkedin',
      ],
      [
        'domains' => ['instagram.com', 'instagr.am'],
        'icon_classes' => 'fa-brands fa-square-instagram',
        'title' => 'Instagram',
      ],
      [
        'domains' => ['youtube.com', 'youtu.be'],
        'icon_classes' => 'fa-brands fa-square-youtube',
        'title' => 'Youtube',
      ],
      [
        'domains' => ['vimeo.com'],
        'icon_classes' => 'fa-brands fa-vimeo',
        'title' => 'Vimeo',
      ],
      [
        'domains' => ['snapchat.com'],
        'icon_classes' => 'fa-brands fa-square-snapchat',
        'title' => 'Snapchat',
      ],
      [
        'domains' => ['soundcloud.com'],
        'icon_classes' => 'fa-brands fa-soundcloud',
        'title' => 'Soundcloud',
      ],
      [
        'domains' => ['spotify.com'],
        'icon_classes' => 'fa-brands fa-spotify',
        'title' => 'Spotify',
      ],
      [
        'domains' => ['apple.com'],
        'icon_classes' => 'fa-brands fa-apple',
        'title' => 'Apple',
      ],
      [
        'domains' => ['telegram.me', 't.me'],
        'icon_classes' => 'fa-brands fa-telegram',
        'title' => 'Telegram',
      ],
      [
        'domains' => ['mailto:'],
        'icon_classes' => 'fa-solid fa-square-envelope',
        'title' => 'Email',
      ],
      [
        'domains' => ['pinterest.com', 'pin.it'],
        'icon_classes' => 'fa-brands fa-square-pinterest',
        'title' => 'Pinterest',
      ],
      [
        'domains' => ['tiktok.com'],
        'icon_classes' => 'fa-brands fa-tiktok',
        'title' => 'Tiktok',
      ],
    ];
  }

  /**
   * Adds the social media icon and title information to a link.
   *
   * @param array $link
   *   The link item.
   *
   * @return array
   *   The updated link item with the icon and social provider title.
   */
  protected function linkWithIcon(array $link): array {
    $url = $link['link_url'];

    $icon_classes = 'fa-solid fa-globe';
    $link_title = $link['link_title'] ?: '';

    foreach ($this->getProviders() as $provider) {
      foreach ($provider['domains'] as $domain) {
        if (strpos($url, $domain) !== FALSE) {
          $icon_classes = $provider['icon_classes'];
          $link_title = $link_title ?: $provider['title'];
          break 2;
        }
      }
    }

    // Use the domain as the link title if the provider is not listed above.
    if (!$link_title && preg_match('/https?:\/\/(.+?)\//', $url, $matches)) {
      $link_title = $matches[1];
    }

    return [
      'link_url' => $url,
      'link_title' => $link_title,
      'icon_classes' => $icon_classes,
    ];
  }

}
