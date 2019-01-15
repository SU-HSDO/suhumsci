<?php

namespace Drupal\su_humsci_profile\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "drupalfontawesome" plugin.
 *
 * @CKEditorPlugin(
 *   id = "humscifontawesome",
 *   label = @Translation("Drupal Font Awesome"),
 *   module = "fontawesome"
 * )
 */
class HumsciFontAwesome extends PluginBase implements CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['drupalfontawesome'];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('profile', 'su_humsci_profile') . '/js/plugin/humscifontawesome.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['core/jquery'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    global $base_path;
    return [
      'humsciFontAwesome' => $base_path . drupal_get_path('profile', 'su_humsci_profile') . '/img/icons/humscifontawesome.png',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

}
