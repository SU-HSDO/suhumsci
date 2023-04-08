<?php

namespace Drupal\hs_views_helper\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\style\Serializer;

/**
 * Class HumsciSerializer overrides the core Serializer to allow customizes.
 *
 * Drupal core does not allow you to change the root tag or the individual item
 * tags. This plugin opens that up to the user.
 *
 * @package Drupal\hs_views_helper\Plugin\views\style
 */
class HumsciSerializer extends Serializer {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['root_tag'] = ['default' => NULL];
    $options['item_tag'] = ['default' => NULL];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['root_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root Tag'),
      '#description' => $this->t('Customize the root name in an XML document. Leave empty for default name.'),
      '#default_value' => $this->options['root_tag'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[formats][xml]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['item_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Tag'),
      '#description' => $this->t('Customize the item name in an XML document. Leave empty for default name.'),
      '#default_value' => $this->options['item_tag'],
      '#states' => [
        'visible' => [
          ':input[name="style_options[formats][xml]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Duplicate everything from the parent class, but change the serialized data
   * and context to set tag names.
   */
  public function render() {
    $preview = FALSE;
    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $preview = TRUE;
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    if ($content_type != 'xml') {
      // Customized tags only work with XML output. If its a json, we just let
      // it do its thing.
      return parent::render();
    }

    $rows = [];
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->renderPlain($row);
    }
    unset($this->view->row_index);

    // This is the customized portion that sets the appropriate tag names.
    $context = ['views_style_plugin' => $this];
    if ($this->options['root_tag']) {
      $context['xml_root_node_name'] = $this->options['root_tag'];
    }

    // For easier changes in views UI, lets format the xml output.
    if ($preview) {
      $context['xml_format_output'] = 'formatOutput';
    }

    $data = $this->options['item_tag'] ? [$this->options['item_tag'] => $rows] : $rows;
    // Now that we have our tag names, serialize the data.
    return $this->serializer->serialize($data, $content_type, $context);
  }

}
