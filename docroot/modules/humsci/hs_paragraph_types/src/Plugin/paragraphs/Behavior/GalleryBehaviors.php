<?php

namespace Drupal\hs_paragraph_types\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Class GalleryBehaviors.
 *
 * @ParagraphsBehavior(
 *   id = "image_gallery",
 *   label = @Translation("Image Gallery"),
 *   description = @Translation("Setting for the image gallery")
 * )
 */
class GalleryBehaviors extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_gallery';
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Mode'),
      '#options' => [
        'default' => $this->t('Grid'),
        'hs_gallery_slideshow' => $this->t('Slideshow'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('image_gallery', 'display_mode', 'default'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $build['#attached']['library'][] = 'hs_paragraph_types/stanford_gallery';
    $build['#attributes']['class'][] = Html::cleanCssIdentifier($paragraph->bundle());
    $build['#attributes']['class'][] = Html::cleanCssIdentifier($view_mode);
  }

}
