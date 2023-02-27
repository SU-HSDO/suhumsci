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
    $profile_path = \Drupal::service('extension.list.profile')
      ->getPath('su_humsci_profile');
    return $profile_path . '/js/plugin/humscifontawesome.js';
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
    $profile_path = \Drupal::service('extension.list.profile')
      ->getPath('su_humsci_profile');
    return [
      'humsciFontAwesome' => $base_path . $profile_path . '/img/icons/humscifontawesome.png',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

}
