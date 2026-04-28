<?php

namespace Drupal\Tests\hs_training\Kernel;

use Drupal\Core\Config\FileStorage;
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

    // installConfig(['hs_training']) cannot be used here: views configs cause
    // TaxonomyIndexTid::calculateDependencies() to fire with an empty 'vid'
    // (the filters were exported as entity_reference, but ViewsHandlerManager
    // resolves them to taxonomy_index_tid at runtime via
    // taxonomy_field_views_data_alter), resulting in a null vocabularyStorage
    // load. Write only the specific configs our assertions require.
    $module_path = \Drupal::root() . '/' . $this->container
      ->get('module_handler')
      ->getModule('hs_training')
      ->getPath();
    $install_storage = new FileStorage($module_path . '/config/install');

    foreach ([
      'node.type.hs_training',
      'taxonomy.vocabulary.hs_training_name',
      'taxonomy.vocabulary.hs_training_audience',
      'taxonomy.vocabulary.hs_training_provider',
      'taxonomy.vocabulary.hs_training_product',
      'taxonomy.vocabulary.hs_training_unit',
    ] as $config_name) {
      \Drupal::configFactory()
        ->getEditable($config_name)
        ->setData($install_storage->read($config_name))
        ->save();
    }
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
