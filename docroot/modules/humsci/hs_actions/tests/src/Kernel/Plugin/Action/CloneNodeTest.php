<?php

namespace Drupal\Tests\hs_actions\Kernel\Plugin\Action;

use Drupal\Core\Form\FormState;
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'type',
    ]);
    $this->node->save();
  }

  /**
   * Test the action methods.
   *
   * @covers ::defaultConfiguration
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
    $this->assertCount(1, $action->defaultConfiguration());
    $this->assertArrayHasKey('clone_count', $action->defaultConfiguration());

    $form = [];
    $form_state = new FormState();
    $this->assertCount(1, $action->buildConfigurationForm($form, $form_state));
    $this->assertArrayHasKey('clone_count', $action->buildConfigurationForm($form, $form_state));

    $form_state->setValue('clone_count', 7);
    $action->submitConfigurationForm($form, $form_state);
    $this->assertEquals(7, $action->getConfiguration()['clone_count']);

    $action->execute($this->node);
    $this->assertEquals(8, $this->getNodeCount());
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
