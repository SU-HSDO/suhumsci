<?php

namespace Drupal\hs_dashboard\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Importer Info annotation object.
 *
 * @see \Drupal\hs_dashboard\Plugin\ImporterInfoManager
 * @see plugin_api
 *
 * @Annotation
 */
class ImporterInfo extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The short description of the plugin for admin interfaces.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
