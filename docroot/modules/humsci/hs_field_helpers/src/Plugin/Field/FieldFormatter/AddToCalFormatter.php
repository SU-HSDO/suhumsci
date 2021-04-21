<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\addtocal\Plugin\Field\FieldFormatter\AddtocalView;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class AddToCalFormatter.
 *
 * @package Drupal\hs_field_helpers\Plugin\Field\FieldFormatter
 */
class AddToCalFormatter extends AddtocalView {

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm([], $form_state);
    unset($element['date_format']);
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach (Element::children($elements) as $delta) {
      $elements[$delta] = $elements[$delta]['addtocal'];
      $elements[$delta]['#button_attributes']['class'][] = 'hs-secondary-button';
    }
    return $elements;
  }

}
