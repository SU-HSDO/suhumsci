<?php

namespace Drupal\hs_field_helpers\Plugin\views\style;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ui_patterns\UiPatternsManager;
use Drupal\ui_patterns\UiPatternsSourceManager;

/**
 * Pattern style plugin to wrap rows in a pattern.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "pattern",
 *   title = @Translation("Pattern"),
 *   help = @Translation("Use a pattern to wrap the rows."),
 *   theme = "views_view_pattern",
 *   display_types = {"normal"}
 * )
 */
class PatternsStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * UI Patterns manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  protected $patternsManager;

  /**
   * UI Patterns manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsSourceManager
   */
  protected $sourceManager;

  /**
   * Pattern constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\UiPatternsManager $patterns_manager
   *   UI Patterns manager.
   * @param \Drupal\ui_patterns\UiPatternsSourceManager $source_manager
   *   UI Patterns source manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UiPatternsManager $patterns_manager, UiPatternsSourceManager $source_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->patternsManager = $patterns_manager;
    $this->sourceManager = $source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns'),
      $container->get('plugin.manager.ui_patterns_source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['pattern'] = [
      '#type' => 'select',
      '#empty_value' => '_none',
      '#title' => $this->t('Pattern'),
      '#options' => $this->patternsManager->getPatternsOptions(),
      '#default_value' => isset($this->options['pattern']) ? $this->options['pattern'] : NULL,
      '#required' => TRUE,
      '#attributes' => ['id' => 'patterns-select'],
    ];

    $this->view->execute();
    $this->buildPatternSourceForm($form, $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $pattern_values = $form_state->getValue('style_options');
    $pattern = $pattern_values['pattern'];
    $pattern_values['pattern_mapping'] = $pattern_values['pattern_mapping'][$pattern]['settings'];
    $form_state->setValue('style_options', $pattern_values);
    parent::validateOptionsForm($form, $form_state);
  }

  /**
   * Create the source -> destination form for each pattern.
   *
   * @param array $form
   *   Built form.
   * @param array $configuration
   *   Current configuration settings.
   */
  public function buildPatternSourceForm(array &$form, array $configuration) {
    foreach (array_keys($this->patternsManager->getDefinitions()) as $pattern_id) {
      $form['pattern_mapping'][$pattern_id] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            'select[id="patterns-select"]' => ['value' => $pattern_id],
          ],
        ],
      ];
      $form['pattern_mapping'][$pattern_id]['settings'] = $this->getMappingForm($pattern_id, $configuration);
    }
  }

  /**
   * Build the source -> destination form for a specific pattern.
   *
   * @param string $pattern_id
   *   Pattern machine name.
   * @param array $configuration
   *   Existing configuration.
   *
   * @return array
   *   Drag table form for the given pattern.
   */
  public function getMappingForm($pattern_id, array $configuration) {
    /** @var \Drupal\ui_patterns\Definition\PatternDefinition $pattern */
    $pattern = $this->patternsManager->getDefinition($pattern_id);

    $elements = [
      '#type' => 'table',
      '#header' => $this->getHeaders(),
    ];
    $elements['#tabledrag'][] = [
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'field-weight',
    ];

    $fields = [];
    foreach ($this->getFields() as $field_name => $label) {
      $weight = (int) $this->getDefaultValue($configuration, $field_name, 'weight');
      $fields[$field_name] = [
        'info' => ['#plain_text' => $label],
        'destination' => [
          '#type' => 'select',
          '#title' => $this->t('Destination for @field', ['@field' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => $this->getDefaultValue($configuration, $field_name, 'destination'),
          '#options' => $pattern->getFieldsAsOptions(),
          '#empty_option' => $this->t('- Not Included In Pattern -'),
        ],
        'weight' => [
          '#type' => 'weight',
          '#default_value' => $weight,
          '#delta' => 20,
          '#title' => $this->t('Weight for @field field', ['@field' => $label]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['field-weight']],
        ],
        '#attributes' => ['class' => ['draggable']],
        '#weight' => $weight,
      ];
    }

    uasort($fields, [SortArray::class, 'sortByWeightProperty']);
    return array_merge($elements, $fields);
  }

  /**
   * Get the table headers.
   *
   * @return array
   *   Array of translated table headers.
   */
  protected function getHeaders() {
    return [
      $this->t('Source'),
      $this->t('Destination'),
      $this->t('Weight'),
    ];
  }

  /**
   * Get all compatible "fields" for the pattern.
   *
   * @return array
   *   All available fields that can be added to the pattern.
   */
  public function getFields() {
    $fields = [
      'rows' => $this->t('View Rows'),
      'title' => $this->t('View Title'),
    ];

    if ($this->view->header) {
      foreach ($this->view->header as $key => $header) {
        $fields["header:$key"] = $header->adminLabel();
      }
    }
    if ($this->view->footer) {
      foreach ($this->view->footer as $key => $footer) {
        $fields["footer:$key"] = $footer->adminLabel();
      }
    }
    return $fields;
  }

  /**
   * Get the current default value of the configuration.
   *
   * @param array $configuration
   *   Existing settings on the view.
   * @param string $field_name
   *   Header/rows/etc to get the value of.
   * @param string $value
   *   Destination or weight.
   *
   * @return null|mixed
   *   The existing value or null.
   */
  public function getDefaultValue(array $configuration, $field_name, $value) {
    if (!empty($configuration['pattern_mapping'][$field_name][$value])) {
      return $configuration['pattern_mapping'][$field_name][$value];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $render = parent::render();

    $pattern_regions = [];
    $this->buildHeaderFooterRegions('header', $pattern_regions);
    $this->buildHeaderFooterRegions('footer', $pattern_regions);

    // Add rows to the pattern.
    $rows_region = $this->options['pattern_mapping']['rows']['destination'];
    $pattern_regions[$rows_region]['rows'] = $render[0]['#rows'];

    // Add the title to the pattern.
    $title_region = $this->options['pattern_mapping']['title']['destination'];
    $pattern_regions[$title_region]['title'] = $this->view->getTitle();

    // Cleanup regions.
    foreach ($pattern_regions as &$region) {
      $region = array_filter($region);
    }

    $render[0]['#rows'] = array_filter($pattern_regions);
    return $render;
  }

  /**
   * Build the header and footer regions of the pattern.
   *
   * @param string $section
   *   Header or footer section.
   * @param array $pattern_regions
   *   Built pattern output.
   */
  protected function buildHeaderFooterRegions($section, array &$pattern_regions) {
    // Get section fields.
    if ($this->view->{$section}) {
      foreach ($this->view->{$section} as $field => $part) {
        if (!isset($this->options['pattern_mapping']["$section:$field"])) {
          continue;
        }

        $section_region = $this->options['pattern_mapping']["$section:$field"]['destination'];
        $pattern_regions[$section_region]["$section:$field"] = $part->render();
        unset($this->view->{$section}[$field]);
      }
    }
  }

}
