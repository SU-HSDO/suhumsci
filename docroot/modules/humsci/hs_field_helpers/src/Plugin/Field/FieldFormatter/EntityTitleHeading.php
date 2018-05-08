<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provide a string field to be used as a heading.
 *
 * @FieldFormatter(
 *   id = "entity_title_heading",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class EntityTitleHeading extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $attributes = new Attribute();
    $classes = $this->getSetting('classes');
    if (!empty($classes)) {
      $attributes->addClass($classes);
    }

    $parent = $items->getParent()->getValue();

    $text = $parent->get('title')->getValue()[0]['value'];

    if ($this->getSetting('linked')) {
      $text = $this->l($text, $parent->toUrl());
    }
    $output[] = [
      '#type' => 'html_tag',
      '#tag' => $this->getSetting('tag'),
      '#attributes' => $attributes->toArray(),
      '#value' => $text,
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'tag' => 'h2',
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    foreach (range(1, 5) as $level) {
      $heading_options['h' . $level] = 'H' . $level;
    }
    $element['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#description' => $this->t('Select the tag which will be wrapped around the title.'),
      '#options' => $heading_options,
      '#default_value' => $this->getSetting('tag'),
    ];
    return $element;
  }

}
