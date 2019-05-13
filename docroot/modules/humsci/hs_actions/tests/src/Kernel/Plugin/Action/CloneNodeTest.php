<?php

namespace Drupal\Tests\hs_actions\Kernel\Plugin\Action;

use Drupal\Core\Form\FormState;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_actions\Plugin\Action\CloneNode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class TestCloneNode.
 *
 * @coversDefaultClass \Drupal\hs_actions\Plugin\Action\CloneNode
 * @group hs_actions
 */
class CloneNodeTest extends KernelTestBase {

  /**
   * Node object to clone.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

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
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('field_storage_config');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATE],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $field->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $field_storage->getName() => date('Y-m-d'),
    ]);
    $this->node->save();
  }

  /**
   * Test the action methods.
   *
   * @covers ::defaultConfiguration
   * @covers ::buildFieldCloneForm
   * @covers ::validateConfigurationForm
   * @covers ::duplicateEntity
   * @covers ::getReferenceFields
   * @covers ::buildConfigurationForm
   * @covers ::submitConfigurationForm
   * @covers ::execute
   */
  public function testAction() {
    $this->assertEquals(1, $this->getNodeCount());

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\hs_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $this->assertEquals(CloneNode::class, get_class($action));

    // Simple methods.
    $this->assertCount(2, $action->defaultConfiguration());
    $this->assertArrayHasKey('clone_count', $action->defaultConfiguration());
    $this->assertArrayHasKey('field_clone', $action->defaultConfiguration());

    $form = [];
    $form_state = new FormState();
    $context = ['list' => [$this->node->id()]];
    $action->setContext($context);
    $this->assertCount(2, $action->buildConfigurationForm($form, $form_state));
    $this->assertArrayHasKey('clone_count', $action->buildConfigurationForm($form, $form_state));

    $form_state->setValue('clone_count', 7);
    $action->validateConfigurationForm($form, $form_state);
    $action->submitConfigurationForm($form, $form_state);
    $this->assertEquals(7, $action->getConfiguration()['clone_count']);

    $action->execute($this->node);
    $this->assertEquals(8, $this->getNodeCount());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\hs_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $this->assertFalse($action->access($this->node));
  }

  /**
   * Get the number of nodes in the database with the name we need.
   *
   * @return int
   *   Count of rows.
   *
   * @throws \Exception
   */
  protected function getNodeCount() {
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');

    return $database->select('node_field_data', 'n')
      ->fields('n')
      ->condition('title', $this->node->getTitle())
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
