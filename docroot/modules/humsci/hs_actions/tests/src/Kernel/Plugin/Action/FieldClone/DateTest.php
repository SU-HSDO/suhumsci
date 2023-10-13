<?php

namespace Drupal\Tests\hs_actions\Kernel\Plugin\Action\FieldClone;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormState;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_actions\Plugin\Action\FieldClone\Date;
use Drupal\hs_actions\Plugin\Action\FieldClone\FieldCloneBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Test the date field clone plugin functions correctly.
 *
 * @group hs_actions
 * @coversDefaultClass \Drupal\hs_actions\Plugin\Action\FieldClone\Date
 */
class DateTest extends KernelTestBase {

  /**
   * Node object to clone.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Date Time field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Current date time object.
   *
   * @var \DateTime
   */
  protected $currentDate;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'user',
    'hs_actions',
    'field',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->currentDate = new \DateTime();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('date_format');
    $this->installSchema('node', 'node_access');

    DateFormat::create([
      'id' => 'fallback',
      'pattern' => 'D, m/d/Y - H:i',
    ])->save();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATETIME],
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $this->field->save();

    $node_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);

    DateFormat::create(['id' => 'medium', 'pattern' => 'F j, Y g:i A'])
      ->save();
    $node_display->setComponent($this->field->getName());
    $node_display->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $this->field->getName() => ['value' => $this->currentDate->format('Y-m-d')],
    ]);
    $this->node->save();
  }

  /**
   * Test the plugin form methods.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testForm() {
    /** @var \Drupal\hs_actions\Plugin\FieldCloneManagerInterface $field_manager */
    $field_manager = $this->container->get('plugin.manager.hs_actions_field_clone');
    /** @var \Drupal\hs_actions\Plugin\Action\FieldClone\Date $plugin */
    $plugin = $field_manager->createInstance('date');
    $this->assertInstanceOf(Date::class, $plugin);
    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildConfigurationForm($form, $form_state);
    $this->assertCount(2, $form);
  }

  /**
   * Test the field clone values works as expected.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testDateFieldClone() {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\hs_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'field_clone' => [
        'date' => [
          $this->field->getName() => [
            'increment' => 3,
            'unit' => 'years',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);
    $nodes = Node::loadMultiple();
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);
    $cloned_field_value = $new_node->get($this->field->getName())->getString();

    $interval = \DateInterval::createFromDateString('3 year');
    $this->currentDate->add($interval);

    $this->assertEquals($this->currentDate->format('Y-m-d'), $cloned_field_value);

    $test_field_base = new TestFieldCloneBase([], NULL, NULL);
    $form = [];
    $form_state = new FormState();
    $this->assertNull($test_field_base->validateConfigurationForm($form, $form_state));
    $this->assertNull($test_field_base->submitConfigurationForm($form, $form_state));
  }

  /**
   * Test when the date is copied over a daylight savings, it displays correct.
   */
  public function testDaylightSavingsFromJune() {
    $this->node->set($this->field->getName(), '2019-06-01T16:15:00');
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\hs_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'field_clone' => [
        'date' => [
          $this->field->getName() => [
            'increment' => 6,
            'unit' => 'months',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);
    $nodes = Node::loadMultiple();
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($this->node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('June 2, 2019 2:15 AM', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('December 2, 2019 2:15 AM', (string) $rendered_output);
  }

  /**
   * Test when the date is copied over a daylight savings, it displays correct.
   */
  public function testDaylightSavingsFromDecember() {
    $this->node->set($this->field->getName(), '2019-12-01T16:15:00');
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\hs_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'field_clone' => [
        'date' => [
          $this->field->getName() => [
            'increment' => 6,
            'unit' => 'months',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);
    $nodes = Node::loadMultiple();
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($this->node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('December 2, 2019 3:15 AM', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('June 2, 2020 3:15 AM', (string) $rendered_output);
  }

}

class TestFieldCloneBase extends FieldCloneBase {

  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $new_entity, $field_name, array $config = []) {
  }

}
