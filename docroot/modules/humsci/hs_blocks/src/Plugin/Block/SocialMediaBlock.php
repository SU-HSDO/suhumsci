<?php

declare(strict_types=1);

namespace Drupal\hs_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\multivalue_form_element\Element\MultiValue;

/**
 * Provides a social media block.
 *
 * @Block(
 *   id = "hs_blocks_social_media_block",
 *   admin_label = @Translation("Social Media Block"),
 *   category = @Translation("H&S Blocks"),
 * )
 */
final class SocialMediaBlock extends BlockBase {

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
      '#default_value' => ($this->configuration['links']) ? $this->configuration['links'] : [],
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
        '#description' => $this->t('If empty the domain name will be used.'),
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
    $links = $form_state->getValue('links');
    foreach ($links as $link) {
      if (!empty($link['link_url'] || !empty($link['link_title']))) {
        $filtered_links[] = $link;
      }
    }

    $this->configuration['links'] = $filtered_links;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#theme' => 'hs_blocks_social_media',
      '#icon_size' => $this->configuration['icon_size'],
      '#layout' => $this->configuration['layout'],
      '#links' => $this->configuration['links'],
      '#cache' => [
        'tags' => $this->getCacheTags(),
        'contexts' => ['user'],
      ],
    ];

    $build['#contextual_links']['hs_blocks.social_media_block'] = [
      'route_parameters' => ['block' => $this->getDerivativeId()],
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
  public static function validateLinks($element, FormStateInterface $form_state) {
    $links = $form_state->getValue($element['#parents']);
    foreach ($links as $key => $link) {
      if (is_array($link)) {
        // Test if there is a title but no URL.
        if (!empty($link['link_title']) && empty($link['link_url'])) {
          $form_state->setErrorByName(
            implode('][', array_merge($element['#parents'], [$key, 'link_url'])),
            t('The URL must be provided if a label is set')
          );
        }
      }
    }
  }

}
