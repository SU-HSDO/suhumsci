<?php

namespace Drupal\su_humsci_profile\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "drupalfontawesome" plugin.
 *
 * @CKEditorPlugin(
 *   id = "humscifontawesome",
 *   label = @Translation("Drupal Font Awesome"),
 *   module = "fontawesome"
 * )
 */
class HumsciFontAwesome extends PluginBase implements ContainerFactoryPluginInterface, CKEditorPluginContextualInterface {

  /**
   * Profile extension list service.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $profileExtension;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.profile')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ExtensionList $profile_extension) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->profileExtension = $profile_extension;
  }

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
    return $this->profileExtension->getPath('su_humsci_profile') . '/js/plugin/humscifontawesome.js';
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
      'humsciFontAwesome' => $base_path . $this->profileExtension->getPath('su_humsci_profile') . '/img/icons/humscifontawesome.png',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

}
