<?php

namespace Drupal\Tests\hs_training\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class HsTrainingKernelTest.
 *
 * @group hs_training
 */
class HsTrainingKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'link',
    'taxonomy',
    'path',
    'path_alias',
    'menu_ui',
    'datetime',
    'options',
    'layout_discovery',
    'smart_date',
    'auto_entitylabel',
    'token',
    'conditional_fields',
    'ds',
    'ctools',
    'pathauto',
    'views',
    'views_infinite_scroll',
    'node_revision_delete',
    'rabbit_hole',
    'rh_node',
    'hs_training',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install node config to get the 'teaser' view mode and body field storage.
    $this->installConfig(['node']);
    // hs_horizontal_card view mode lives in config/default (not any module's
    // config/install), so create it here before installing hs_training config.
    \Drupal::entityTypeManager()->getStorage('entity_view_mode')->create([
      'id' => 'node.hs_horizontal_card',
      'label' => 'Horizontal Card',
      'targetEntityType' => 'node',
    ])->save();
    $this->installConfig(['hs_training']);
  }

  /**
   * The hs_training node type is created on module install.
   */
  public function testNodeTypeExists() {
    $node_type = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->load('hs_training');
    $this->assertNotEmpty($node_type);
  }

  /**
   * All training taxonomy vocabularies are created on module install.
   */
  public function testVocabulariesExist() {
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
    $vocabularies = [
      'hs_training_name',
      'hs_training_audience',
      'hs_training_provider',
      'hs_training_product',
      'hs_training_unit',
    ];
    foreach ($vocabularies as $vid) {
      $this->assertNotEmpty($storage->load($vid), "Vocabulary '$vid' should exist after module install.");
    }
  }

}
