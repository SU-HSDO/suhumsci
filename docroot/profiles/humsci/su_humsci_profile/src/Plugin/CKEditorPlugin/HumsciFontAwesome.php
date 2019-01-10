<?php

namespace Drupal\su_humsci_profile\Plugin\CKEditorPlugin;

use Drupal\fontawesome\Plugin\CKEditorPlugin\DrupalFontAwesome;

class HumsciFontAwesome extends DrupalFontAwesome {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('profile', 'su_humsci_profile') . '/js/plugins/drupalfontawesome/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $buttons = parent::getButtons();
    $buttons['DrupalFontAwesome']['image'] = drupal_get_path('profile', 'su_humsci_profile') .'/js/plugins/drupalfontawesome/icons/drupalfontawesome.png';
    return $buttons;
  }

}
