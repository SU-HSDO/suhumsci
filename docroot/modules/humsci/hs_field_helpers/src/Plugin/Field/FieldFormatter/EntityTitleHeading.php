<?php

namespace Drupal\hs_field_helpers\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a string field to be used as a heading.
 *
 * @FieldFormatter(
 *   id = "entity_title_heading",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class EntityTitleHeading extends FormatterBase implements ContainerFactoryPluginInterface {

  protected $pathMatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, $path_matcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Home page we don't want to display an H1 tag (page title).
    if ($this->pathMatcher->isFrontPage() && $this->getSetting('tag') == 'h1') {
      return [];
    }

    $attributes = new Attribute();
    $classes = $this->getSetting('classes');
    if (!empty($classes)) {
      $attributes->addClass($classes);
    }

    $parent = $items->getParent()->getValue();

    $text = $parent->get('title')->getValue()[0]['value'];

    if ($this->getSetting('linked')) {
      $text = Link::fromTextAndUrl($text, $parent->toUrl())->toString();
    }
    $output[] = [
      '#type' => 'html_tag',
      '#tag' => $this->getSetting('tag'),
      '#attributes' => $attributes->toArray(),
      '#value' => $text,
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'tag' => 'h2',
    ];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    foreach (range(1, 5) as $level) {
      $heading_options['h' . $level] = 'H' . $level;
    }
    $element['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#description' => $this->t('Select the tag which will be wrapped around the title.'),
      '#options' => $heading_options,
      '#default_value' => $this->getSetting('tag'),
    ];
    return $element;
  }

}
