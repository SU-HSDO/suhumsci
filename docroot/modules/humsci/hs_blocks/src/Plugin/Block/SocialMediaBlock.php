<?php

declare(strict_types=1);

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a social media block.
 */
#[Block(
  id: "hs_blocks_social_media_block",
  admin_label: new TranslatableMarkup("Social Media Block"),
  category: new TranslatableMarkup("H&S Blocks"),
)]
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
    // If the form is being rendered for the first time, need to set the links
    // in the form_state, because we'll use them to generate the form elements.
    if (is_null($form_state->get('links'))) {
      $links = $this->configuration['links'];
      // Add an empty item at the bottom, to make it easier for users to add
      // new links.
      $weight = !empty($links) ? $links[count($links) - 1]['_weight'] + 1 : 0;
      $links[] = [
        'link_title' => '',
        'link_url' => '',
        '_weight' => $weight,
      ];
      $form_state->set('links', $links);
    }

    $form['above'] = [
      '#type' => 'details',
      '#title' => t('Above'),
      '#open' => FALSE,
    ];
    $form['above']['text_above'] = [
      '#type' => 'text_format',
      '#title' => 'This content will display above the icons',
      '#format' => 'basic_html',
      '#allowed_formats' => ['basic_html'],
      '#base_type' => 'textarea',
      '#rows' => 7,
    ];

    $form['icons'] = [
      '#type' => 'details',
      '#title' => t('Icons'),
      '#open' => TRUE,
    ];
    $form['icons']['icon_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Icon Size'),
      '#options' => [
        'small' => $this->t('Small (32px)'),
        'large' => $this->t('Large (48px)'),
      ],
      '#default_value' => $this->configuration['icon_size'],
      '#required' => TRUE,
    ];

    $form['icons']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Layout'),
      '#options' => [
        'grid' => $this->t('Grid (no visible label)'),
        'vertical_list' => $this->t('Vertical List (with visible label)'),
      ],
      '#default_value' => $this->configuration['layout'],
      '#required' => TRUE,
    ];

    $form['icons']['links'] = [
      '#type' => 'container',
      '#field_name' => 'links',
      '#title' => $this->t('Links'),
      '#input' => TRUE,
      '#theme' => 'field_multiple_value_form',
      '#cardinality_multiple' => TRUE,
      '#cardinality' => -1,
      '#description' => $this->t(
        '<p>Supported social platforms will show their icon, otherwise a generic icon will be shown.</p><p>See which <a href="@user_guide_url" target="_blank">social platforms are currently supported</a>.</p>',
        ['@user_guide_url' => 'https://hsweb.slite.page/p/NeJL89GqNsiOY-/Social-Media-Footer-block']
      ),
      '#add_more_label' => $this->t('Add another item'),
      '#element_validate' => [
        [get_class($this), 'validateLinks'],
      ],
      '#attributes' => [
        'id' => 'links-wrapper',
      ],
    ];

    foreach ($form_state->get('links') as $key => $link) {
      $form['icons']['links'][$key] = [
        '#type' => 'container',
        'link_url' => [
          '#type' => 'textfield',
          '#title' => $this->t('URL'),
          '#description' => $this->t('Social Media Profile URL. Must start with https:// or mailto:'),
          '#default_value' => $link['link_url'],
          '#element_validate' => [
            [get_class($this), 'validateUrl'],
          ],
        ],
        'link_title' => [
          '#type' => 'textfield',
          '#title' => $this->t('Label'),
          '#description' => $this->t('If empty, the social platform name will be used for supported platforms, otherwise the domain name will be used.'),
          '#default_value' => $link['link_title'],
        ],
        '_weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight'),
          '#title_display' => 'invisible',
          '#delta' => count($this->configuration['links']),
          '#default_value' => $link['_weight'],
        ],
      ];
    }

    $form['icons']['links']['add_more'] = [
      '#type' => 'submit',
      '#name' => 'links_add_more',
      '#value' => $form['icons']['links']['#add_more_label'],
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => 'links-wrapper',
        'effect' => 'fade',
      ],
    ];
    $form['below'] = [
      '#type' => 'details',
      '#title' => t('Below'),
      '#open' => FALSE,
    ];
    $form['below']['text_below'] = [
      '#type' => 'text_format',
      '#title' => 'This content will display below the icons',
      '#format' => 'basic_html',
      '#allowed_formats' => ['basic_html'],
      '#base_type' => 'textarea',
      '#rows' => 7,
    ];

    return $form;
  }

  /**
   * Custom validation for the link_url field.
   */
  public static function validateUrl(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $element['#value'];

    if (empty($value)) {
      return;
    }

    $mailto_regex = '/^mailto:[\w.%+-]+@[A-Za-z0-9-]+\.[A-Za-z]{2,}(?:\?[^\s]*)?$/i';

    if (str_starts_with($value, 'mailto')) {
      if (!preg_match($mailto_regex, $value)) {
        $form_state->setError($element, t('The mailto link must include a valid email address (e.g., mailto:example@example.com).'));
      }
      return;
    }

    URL::validateUrl($element, $form_state, $complete_form);

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['icon_size'] = $form_state->getValue('icon_size');
    $this->configuration['layout'] = $form_state->getValue('layout');

    // Only save links if they're not empty.
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
          t('The URL must be provided if a label is set.')
        );
      }
    }
  }

  /**
   * Submit handler for the "Add another item" button in the "Links" element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state): void {
    $links = $form_state->get('links');
    // Add new empty element at the bottom
    // (weight greater than the last current element).
    $weight = !empty($links) ? $links[count($links) - 1]['_weight'] + 1 : 0;
    $links[] = [
      'link_url' => '',
      'link_title' => '',
      '_weight' => $weight,
    ];
    $form_state->set('links', $links);
    $form_state->setRebuild();
  }

  /**
   * Ajax Callback for the "Add another item" button in the "Links" element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form element to replace.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state): array {
    return $form['settings']['links'];
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
        'title' => 'X',
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
      [
        'domains' => ['bsky.app', 'bsky.social'],
        'icon_classes' => 'fa-brands fa-square-bluesky',
        'title' => 'Bluesky',
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
