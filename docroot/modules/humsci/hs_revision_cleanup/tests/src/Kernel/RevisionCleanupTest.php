<?php

namespace Drupal\Tests\hs_revision_cleanup\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class RevisionCleanupTest
 *
 * @coversDefaultClass \Drupal\hs_revision_cleanup\RevisionCleanup
 * @group hs_revision_cleanup
 */
class RevisionCleanupTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'hs_revision_cleanup',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    NodeType::create([
      'type' => 'article',
      'new_revision' => TRUE,
    ])->save();
    $this->installSchema('node', 'node_access');
    $node = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
    ]);
    $node->save();
    for ($i = 0; $i <= 5; $i++) {
      $node = Node::load($node->id());
      $node->set('title', $this->randomString());
      $node->setNewRevision();
      $node->save();
    }

    $this->installConfig('hs_revision_cleanup');
    $this->config('hs_revision_cleanup.settings')
      ->set('cleanup.1', ['entity_type' => 'user', 'keep' => 2])
      ->save();
  }

  /**
   * @covers ::__construct
   * @covers ::deleteRevisions
   * @covers ::deleteEntityRevisions
   * @covers ::getPossibleRevisionsIds
   */
  public function testCleanup() {
    $this->assertEquals(7, $this->getRevisionCount());
    \Drupal::service('hs_revision_cleanup')->deleteRevisions();
    $this->assertEquals(5, $this->getRevisionCount());
    \Drupal::service('hs_revision_cleanup')->deleteRevisions();
    $this->assertEquals(5, $this->getRevisionCount());
  }

  /**
   * Get the number of node revisions currently in the database.
   *
   * @return int
   *   Total revisions.
   */
  protected function getRevisionCount() {
    return (int) \Drupal::database()
      ->select('node_revision', 'n')
      ->fields('n')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
