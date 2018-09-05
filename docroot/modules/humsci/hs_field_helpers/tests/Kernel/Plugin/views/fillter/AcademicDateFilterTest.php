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
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
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
   * @covers ::inException
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
      /** @var AcademicDateFilter $filter */
      $filter = $filter_manager->createInstance('academic_datetime', $configuration);
      $this->assertEquals(AcademicDateFilter::class, get_class($filter));

      $this->setFilterValues($filter);
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

      $filter = new TestAcademicDateFilter();
      $this->setFilterValues($filter);
      $this->assertTrue($filter->inException());

      // One full year of exception excluding the current day.
      $filter->options['exception']['start_month'] = date('n');
      $filter->options['exception']['start_day'] = date('j') + 1;
      $filter->options['exception']['end_day'] = date('j') - 1;
      $this->assertFalse($filter->inException());

      $filter->options['exception']['end_month'] = 'garbage';
      $this->assertFalse($filter->inException());
    }

  /**
   * @covers ::opSimple
   * @covers ::opBetween
   * @covers ::inException
   */
  public function testOperations() {
    $view = Views::getView('test_filters');
    $view->execute();
    $this->assertNotEmpty($view->result);

    $view = Views::getView('test_filters');
    $display_filters =  &$view->storage->getDisplay('default')['display_options']['filters'];
    $display_filters[$this->field->getName()] = [
      'id' => $this->field->getName() . '_value',
      'table' => 'node__' . $this->field->getName(),
      'field' => $this->field->getName() . '_value',
      'relationship' => 'none',
      'group_type' => 'group',
      'exposed' => FALSE,
      'operator' => '>=',
      'value' => ['value' => 'now +5days', 'type' => 'date'],
      'plugin_id' => 'academic_datetime',
      'is_grouped' => FALSE,
      'exception' => [
        'exception' => 0,
        'start_month' => (date('n') - 1 ?: 12),
        'start_day' => date('j'),
        'end_month' => date('n'),
        'end_day' => date('j'),
        'value' => 'now -5days',
        'min' => 'now -15days',
        'max' => 'now +45days',
      ],
    ];
    $view->save();
    $view->execute();
    $this->assertEmpty($view->result);

    $view = Views::getView('test_filters');
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'];
    $display_filters[$this->field->getName()]['exception']['exception'] = 1;

    $view->save();
    $view->execute();
    $this->assertNotEmpty($view->result);

    // Reload the view to clear data from previous execution.
    $view = Views::getView('test_filters');
    $display_filters = &$view->storage->getDisplay('default')['display_options']['filters'];
    $display_filters[$this->field->getName()]['operator'] = 'between';
    $display_filters[$this->field->getName()]['value']['min'] = 'now -5days';
    $display_filters[$this->field->getName()]['value']['max'] = 'now +1year';

    $view->save();
    $view->execute();
    $this->assertNotEmpty($view->result);
  }

  /**
   * @param $filter
   */
  protected function setFilterValues(AcademicDateFilter $filter) {
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
  }

}

/**
 * Overrides for Class TestAcademicDateFilter.
 */
class TestAcademicDateFilter extends AcademicDateFilter {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function opBetween($field) {
    parent::opBetween($field);
  }

  /**
   * {@inheritdoc}
   */
  public function opSimple($field) {
    parent::opSimple($field);
  }

  /**
   * {@inheritdoc}
   */
  public function inException() {
    return parent::inException();
  }

}
