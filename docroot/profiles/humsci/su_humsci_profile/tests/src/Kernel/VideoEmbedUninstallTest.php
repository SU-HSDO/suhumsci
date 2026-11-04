<?php

namespace Drupal\Tests\su_humsci_profile\Kernel;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Utility\UpdateException;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Ensures uninstall preserves core video and refuses unexpected legacy usage.
 */
#[Group('su_humsci_profile')]
class VideoEmbedUninstallTest extends KernelTestBase {

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
    'media',
    'migrate',
    'migrate_plus',
    'entity_test',
    'video_embed_field',
    'video_embed_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('media');
    $this->installConfig(['system', 'field', 'media']);
    require_once DRUPAL_ROOT . '/profiles/humsci/su_humsci_profile/su_humsci_profile.install';

    $this->config('migrate_plus.migration.hs_events_importer')->setData([
      'status' => FALSE,
      'process' => [
        'field_hs_event_video' => [
          ['plugin' => 'skip_on_empty', 'source' => 'drupal_video'],
          [
            'plugin' => 'entity_generate',
            'entity_type' => 'media',
            'bundle' => 'video',
            'value_key' => 'field_media_video_embed_field',
          ],
        ],
        'custom_title' => 'site_specific_source',
      ],
    ])->save();
  }

  /**
   * The known stale mapping is repaired while core video and content survive.
   */
  public function testUninstallAndRerun(): void {
    $type = MediaType::create([
      'id' => 'video',
      'label' => 'Video',
      'source' => 'oembed:video',
      'source_configuration' => ['source_field' => 'field_media_oembed_video'],
    ]);
    $type->save();
    $field = $type->getSource()->createSourceField($type);
    $source_storage = $field->getFieldStorageDefinition();
    $this->assertInstanceOf(FieldStorageConfig::class, $source_storage);
    $source_storage->save();
    $field->save();
    $original_type = $type->toArray();
    $original_field = $field->toArray();
    $entity = EntityTestRev::create(['name' => 'Preserved content']);
    $entity->save();

    $this->assertStringContainsString('Uninstalled 2', su_humsci_profile_update_11005());
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('video_embed_field'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('video_embed_media'));
    $this->assertSame($original_type, MediaType::load('video')->toArray());
    $this->assertSame($original_field, FieldConfig::load($field->id())->toArray());
    $this->assertSame('Preserved content', EntityTestRev::load($entity->id())->label());
    $migration = $this->config('migrate_plus.migration.hs_events_importer');
    $this->assertSame('field_media_oembed_video', $migration->get('process.field_hs_event_video.1.value_key'));
    $this->assertFalse($migration->get('status'));
    $this->assertSame('site_specific_source', $migration->get('process.custom_title'));
    $this->assertStringContainsString('already uninstalled', su_humsci_profile_update_11005());
  }

  /**
   * Both active and deleted field data must block the uninstall.
   */
  #[DataProvider('fieldStates')]
  public function testLegacyFieldBlocksUninstall(bool $deleted): void {
    $storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test_rev',
      'field_name' => 'field_legacy_video',
      'type' => 'video_embed_field',
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_storage' => $storage,
      'bundle' => 'entity_test_rev',
    ]);
    $field->save();
    $entity = EntityTestRev::create([
      'name' => 'Legacy content',
      'field_legacy_video' => 'https://www.youtube.com/watch?v=example',
    ]);
    $entity->save();
    if ($deleted) {
      $field->delete();
    }

    $this->assertBlocked();
    $entity_storage = \Drupal::entityTypeManager()->getStorage('entity_test_rev');
    $this->assertInstanceOf(FieldableEntityStorageInterface::class, $entity_storage);
    $this->assertSame(1, $entity_storage->countFieldData($storage));
  }

  /**
   * Provides legacy field storage states.
   */
  public static function fieldStates(): array {
    return ['active' => [FALSE], 'deleted' => [TRUE]];
  }

  /**
   * A site-created legacy media type must not be deleted by uninstall.
   */
  public function testLegacyMediaTypeBlocksUninstall(): void {
    $type = MediaType::create([
      'id' => 'legacy_video',
      'label' => 'Legacy video',
      'source' => 'video_embed_field',
    ]);
    $type->save();
    $this->assertBlocked();
    $this->assertNotNull(MediaType::load('legacy_video'));
  }

  /**
   * Raw plugin references without declared dependencies still need review.
   */
  public function testSiteCustomizationBlocksUninstall(): void {
    $this->container->get('config.storage')->write('custom.video_display', ['formatter' => 'video_embed_field_video']);
    $this->assertBlocked();
    $this->assertSame('video_embed_field_video', $this->config('custom.video_display')->get('formatter'));
  }

  /**
   * Unrelated enabled dependents must never be uninstalled automatically.
   */
  public function testDependentModuleBlocksUninstall(): void {
    $this->enableModules(['filter', 'editor', 'ckeditor5', 'video_embed_wysiwyg']);
    $this->assertBlocked();
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('video_embed_wysiwyg'));
  }

  /**
   * Ensure refusal leaves both modules and the migration unchanged.
   */
  private function assertBlocked(): void {
    try {
      su_humsci_profile_update_11005();
      $this->fail('Unexpected legacy usage must block uninstall.');
    }
    catch (UpdateException $exception) {
      $this->assertStringContainsString('Video embed uninstall blocked', $exception->getMessage());
    }
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('video_embed_field'));
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('video_embed_media'));
    $this->assertSame('field_media_video_embed_field', $this->config('migrate_plus.migration.hs_events_importer')->get('process.field_hs_event_video.1.value_key'));
  }

}
