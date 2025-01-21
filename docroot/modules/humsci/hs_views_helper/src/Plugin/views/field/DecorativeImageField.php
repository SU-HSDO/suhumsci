<?php 

namespace Drupal\hs_views_helper\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Exposes a "Decorative Image" field for Views.
 *
 * @ViewsField("decorative_image_field")
 */
class DecorativeImageField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->query->addWhereExpression(0, "{$this->tableAlias}.field_media_image__alt = '[decorative]'");
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Access the value of the 'decorative' field.
    $decorative = $this->getValue($values);
    
    // Return Yes if the field is checked, else No.
    return $decorative ? $this->t('Yes') : $this->t('No');
  }

}