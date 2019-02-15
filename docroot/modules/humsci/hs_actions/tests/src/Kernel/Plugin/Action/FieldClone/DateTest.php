<?php

namespace Drupal\Tests\hs_actions\Kernel\Plugin\Action\FieldClone;

use Drupal\Core\Form\FormState;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_actions\Plugin\Action\CloneNode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class DateTest
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
  protected function setUp() {
    parent::setUp();
    $this->currentDate = new \DateTime();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATE],
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $this->field->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $this->field->getName() => ['value' => $this->currentDate->format('Y-m-d')],
    ]);
    $this->node->save();
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
  }

}
