<?php

declare(strict_types=1);

namespace Drupal\hs_actions\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Plugin attribute class for field clone plugins.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FieldClone extends Plugin {

  /**
   * Constructs a FieldClone attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   The label of the field clone plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) A description of the field clone plugin.
   * @param string[] $fieldTypes
   *   The field types this plugin can be used with.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    protected readonly array $fieldTypes = [],
  ) {

  }

}
