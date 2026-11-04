<?php

namespace Drupal\Tests\hs_admin\Kernel;

use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that the removal audit detects data outside current entity values.
 */
#[Group('hs_admin')]
class VideoEmbedFieldAuditTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'file',
    'image',
    'entity_test',
    'video_embed_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_rev');
    $this->installConfig(['system', 'field']);
  }

  /**
   * Module installation and core oEmbed configuration do not indicate usage.
   */
  public function testCoreVideoIsClear(): void {
    $this->container->get('config.storage')->write('media.type.video', [
      'id' => 'video',
      'source' => 'oembed:video',
      'source_configuration' => ['source_field' => 'field_media_oembed_video'],
    ]);

    $report = $this->audit();
    $this->assertSame('clear', $report['status']);
    $this->assertTrue($report['modules']['video_embed_field']);
    $this->assertSame([], $report['fields']);
    $this->assertSame([], $report['media_types']);
    $this->assertSame([], $report['config_references']);
  }

  /**
   * Empty fields and values present only in historical revisions need review.
   */
  public function testEmptyFieldsAndHistoricalData(): void {
    $field = $this->createVideoField();
    $report = $this->audit();
    $this->assertSame('review', $report['status']);
    $this->assertSame(0, $report['fields'][0]['entities_with_data']);
    $this->assertSame(['entity_test_rev'], $report['fields'][0]['bundles']);

    $entity = EntityTestRev::create([
      'name' => 'Legacy video',
      $field->getName() => 'https://www.youtube.com/watch?v=example',
    ]);
    $entity->save();
    $entity->setNewRevision(TRUE);
    $entity->set($field->getName(), []);
    $entity->save();

    $this->assertTrue($entity->hasField($field->getName()));
    $this->assertTrue($entity->get($field->getName())->isEmpty());
    $report = $this->audit();
    $this->assertSame(1, $report['fields'][0]['entities_with_data']);
    $this->assertFalse($report['fields'][0]['deleted']);
  }

  /**
   * Deleted field instances and storage awaiting purge remain visible.
   */
  public function testDeletedFieldData(): void {
    $field = $this->createVideoField();
    EntityTestRev::create([
      'name' => 'Deleted legacy video field',
      $field->getName() => 'https://www.youtube.com/watch?v=example',
    ])->save();
    $field->delete();

    $report = $this->audit();
    $this->assertSame('review', $report['status']);
    $this->assertSame(['entity_test_rev.entity_test_rev.field_legacy_video'], $report['deleted_field_instances']);
    $this->assertSame(1, $report['fields'][0]['entities_with_data']);
    $this->assertFalse($report['fields'][0]['deleted']);

    $storage = FieldStorageConfig::load('entity_test_rev.field_legacy_video');
    $this->assertNotNull($storage);
    $storage->delete();

    $report = $this->audit();
    $this->assertSame('review', $report['status']);
    $this->assertTrue($report['fields'][0]['deleted']);
    $this->assertSame(1, $report['fields'][0]['entities_with_data']);
  }

  /**
   * Per-site media sources, formatters, and stale migration mappings are listed.
   */
  public function testConfigurationReferences(): void {
    $config_storage = $this->container->get('config.storage');
    $config_storage->write('media.type.legacy_video', [
      'id' => 'legacy_video',
      'source' => 'video_embed_field',
    ]);
    $config_storage->write('core.entity_view_display.media.legacy_video.default', [
      'content' => ['field_video' => ['type' => 'video_embed_field_video']],
    ]);
    $config_storage->write('migrate_plus.migration.hs_events_importer', [
      'process' => ['value_key' => 'field_media_video_embed_field'],
    ]);

    $report = $this->audit();
    $this->assertSame('review', $report['status']);
    $this->assertSame(['legacy_video'], $report['media_types']);
    $this->assertSame([
      'core.entity_view_display.media.legacy_video.default',
      'media.type.legacy_video',
      'migrate_plus.migration.hs_events_importer',
    ], $report['config_references']);
  }

  /**
   * Create a field whose storage survives deletion of its final instance.
   */
  private function createVideoField(): FieldConfig {
    $storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test_rev',
      'field_name' => 'field_legacy_video',
      'type' => 'video_embed_field',
      'persist_with_no_fields' => TRUE,
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'entity_test_rev',
    ]);
    $field->save();
    return $field;
  }

  /**
   * Run the same script sent to each site by the Drush report command.
   */
  private function audit(): array {
    ob_start();
    try {
      require dirname(__DIR__, 7) . '/drush/scripts/video-embed-field-audit.php';
      return json_decode(ob_get_contents(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    finally {
      ob_end_clean();
    }
  }

}
