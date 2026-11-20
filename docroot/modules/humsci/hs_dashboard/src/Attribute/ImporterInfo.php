<?php

declare(strict_types=1);

namespace Drupal\hs_dashboard\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an Importer Info attribute object.
 *
 * @see \Drupal\hs_dashboard\Plugin\ImporterInfoManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ImporterInfo extends Plugin {

  /**
   * Constructs an ImporterInfo attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The short description of the plugin for admin interfaces.
   * @param int $weight
   *   (optional) The default weight of plugin instances used for sorting.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
    public readonly ?string $deriver = NULL,
  ) {}

}
