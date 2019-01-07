<?php

namespace Drupal\Tests\hs_field_helpers\Kernel\Plugin\views\filter;

use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_field_helpers\Plugin\views\filter\AcademicDateFilter;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;

/**
 * Class AcademicDateFilterTest.
 *
 * @coversDefaultClass \Drupal\hs_field_helpers\Plugin\views\filter\AcademicDateFilter
 * @group hs_field_helpers
 */
class AcademicDateFilterTest extends KernelTestBase {

  /**
   * Field Storage entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * Field Config entity.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'hs_field_helpers',
    'views',
    'views_ui',
    'system',
    'field',
    'datetime',
    'node',
    'user',
    'hs_field_helpers_test_config',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig([
      'system',
      'field',
      'node',
      'hs_field_helpers_test_config',
    ]);

    $this->setupNodes();
  }

  /**
   * Install node type and with field and create a node.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setupNodes() {
    date_default_timezone_set('America/Los_Angeles');
    NodeType::create([
      'type' => 'page',
      'name' => 'page',
    ])->save();
    $field_name = mb_strtolower($this->randomMachineName());

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'datetime',
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'page',
    ]);
    $this->field->save();

    $node = Node::create([
      'title' => 'Test node title',
      'type' => 'page',
      $this->field->getName() => [['value' => date('Y-m-d')]],
    ]);
    $node->save();
  }

  /**
   * Coverage tests for Exception filter.
   *
   * @covers ::hasExtraOptions
   * @covers ::buildExtraOptionsForm
   * @covers ::adminSummary
   */
  public function testAcademicFilter() {
    /** @var \Drupal\views\Plugin\ViewsHandlerManager $filter_manager */
    $filter_manager = $this->container->get('plugin.manager.views.filter');
    $filters = $filter_manager->getDefinitions();
    $this->assertArrayHasKey('academic_datetime', $filters);
    $configuration = [
      'entity_type' => 'node',
      'field' => $this->field->getName() . '_value',
      'table' => 'node__' . $this->field->getName(),
      'id' => 'academic_datetime',
      'field_name' => $this->field->getName(),
    ];
    /** @var \Drupal\hs_field_helpers\Plugin\views\filter\AcademicDateFilter $filter */
    $filter = $filter_manager->createInstance('academic_datetime', $configuration);
    $this->assertEquals(AcademicDateFilter::class, get_class($filter));

    $filter->value['value'] = 'now';
    $filter->options['exception'] = [
      'exception' => 1,
      'start_month' => date('n') - 1 ?: 12,
      'start_day' => date('j'),
      'end_month' => date('n'),
      'end_day' => date('j'),
      'value' => 'now +30days',
      'min' => 'now -15days',
      'max' => 'now +45days',
    ];

    $this->assertEquals('= now Exception: = now +30days', $filter->adminSummary());
    $this->assertTrue($filter->hasExtraOptions());

    $form_state = new FormState();
    $form = [];
    $filter->buildExtraOptionsForm($form, $form_state);
    $this->assertArrayHasKey('exception', $form);

    $filter->operator = '=';
    $form = [];
    $filter->buildExtraOptionsForm($form, $form_state);
    $this->assertArrayHasKey('value', $form['exception']);
    $this->assertArrayNotHasKey('min', $form['exception']);

    $filter->operator = 'between';
    $form = [];
    $filter->buildExtraOptionsForm($form, $form_state);
    $this->assertArrayHasKey('min', $form['exception']);
    $this->assertArrayNotHasKey('value', $form['exception']);

    $form_state->set('exposed', TRUE);
    $form = [];
    $filter->buildExtraOptionsForm($form, $form_state);
    $this->assertArrayNotHasKey('exception', $form);
  }

  /**
   * Test the filter gives correct results.
   *
   * @covers ::opSimple
   * @covers ::opBetween
   * @covers ::inException
   */
  public function testOperations() {
    $field_name = $this->field->getName();

    // Test without any filters.
    $view = $this->getView();
    $view->storage->getDisplay('default')['display_options']['filters'] = [];
    $view->execute();
    $this->assertNotEmpty($view->result);

    // Test with the filter but not the exception filter.
    $view = $this->getView();
    $view->storage->getDisplay('default')['display_options']['filters'][$field_name]['exception']['exception'] = 0;
    $view->execute();
    $this->assertEmpty($view->result);

    // Test with the filter and the exception.
    $view = $this->getView();
    $view->execute();
    $this->assertNotEmpty($view->result);

    // Test between operation.
    $view = $this->getView();
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'][$field_name];
    $display_filters['operator'] = 'between';
    $view->execute();
    $this->assertNotEmpty($view->result);

    // Test over the new year.
    $view = $this->getView();
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'][$field_name];
    $display_filters['exception']['start_month'] = 12;
    $display_filters['exception']['start_day'] = 31;
    $display_filters['exception']['end_month'] = date('j', time() - 60 * 60 * 24) - 1;
    $display_filters['exception']['end_day'] = date('j', time() - 60 * 60 * 24) - 1;
    $view->execute();
    $this->assertEmpty($view->result);

    // Test try catch for invalid exception dates.
    $view = $this->getView();
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'][$field_name];
    $display_filters['exception']['end_month'] = 'garbage';
    $view->execute();
    $this->assertEmpty($view->result);
  }

  /**
   * Get the test filter view with the field filter set.
   *
   * @return \Drupal\views\ViewExecutable
   *   The filtered test view.
   */
  protected function getView() {
    // Load the view each time to remove any results.
    $view = Views::getView('test_filters');
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'];

    $display_filters[$this->field->getName()] = [
      'id' => $this->field->getName() . '_value',
      'table' => 'node__' . $this->field->getName(),
      'field' => $this->field->getName() . '_value',
      'relationship' => 'none',
      'group_type' => 'group',
      'exposed' => FALSE,
      'operator' => '>=',
      'value' => [
        'min' => 'now -5days',
        'max' => 'now +5days',
        'value' => 'now +5days',
        'type' => 'offset',
      ],
      'plugin_id' => 'academic_datetime',
      'is_grouped' => FALSE,
      'exception' => [
        'exception' => TRUE,
        'start_month' => date('n', time() - 60 * 60 * 24),
        'start_day' => date('j', time() - 60 * 60 * 24),
        'end_month' => date('n', time() + 60 * 60 * 24),
        'end_day' => date('j', time() + 60 * 60 * 24),
        'value' => 'now -5days',
        'min' => 'now -15days',
        'max' => 'now +45days',
      ],
    ];

    return $view;
  }

}
