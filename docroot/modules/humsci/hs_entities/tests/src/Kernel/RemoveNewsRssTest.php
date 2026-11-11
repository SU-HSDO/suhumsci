<?php

namespace Drupal\Tests\hs_entities\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\hs_entities\Entity\HsEntity;
use Drupal\hs_entities\Entity\HsEntityType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests removal of News RSS data and dependent configuration.
 */
#[Group('hs_entities')]
#[RunTestsInSeparateProcesses]
class RemoveNewsRssTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field', 'text', 'link', 'taxonomy', 'auto_entitylabel', 'hs_entities'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('hs_entity');
    $this->container->get('module_handler')->loadInclude('hs_entities', 'install');

    HsEntityType::create(['id' => 'news_rss', 'label' => 'News RSS'])->save();
    HsEntityType::create(['id' => 'other', 'label' => 'Other'])->save();

    foreach (['field_terms' => 'entity_reference', 'field_url' => 'link'] as $name => $type) {
      FieldStorageConfig::create([
        'entity_type' => 'hs_entity',
        'field_name' => $name,
        'type' => $type,
        'settings' => $type === 'entity_reference' ? ['target_type' => 'taxonomy_term'] : [],
      ])->save();
      FieldConfig::create([
        'entity_type' => 'hs_entity',
        'bundle' => 'news_rss',
        'field_name' => $name,
      ])->save();
    }

    foreach ([EntityFormDisplay::class, EntityViewDisplay::class] as $display_class) {
      $display_class::create([
        'targetEntityType' => 'hs_entity',
        'bundle' => 'news_rss',
        'mode' => 'default',
      ])->save();
    }

    $this->config('auto_entitylabel.settings.hs_entity.news_rss')
      ->set('pattern', 'News RSS feed.')
      ->save();
    Vocabulary::create(['vid' => 'topics', 'name' => 'Topics'])->save();
  }

  /**
   * News RSS removal preserves other bundles and shared taxonomy data.
   */
  #[DataProvider('removalCases')]
  public function testRemoval(int $count, bool $shared_field): void {
    $term = Term::create(['vid' => 'topics', 'name' => 'News topic']);
    $term->save();
    if ($shared_field) {
      FieldConfig::create([
        'entity_type' => 'hs_entity',
        'bundle' => 'other',
        'field_name' => 'field_terms',
      ])->save();
    }
    $other = HsEntity::create([
      'bundle' => 'other',
      'label' => 'Retained entity',
    ]);
    if ($shared_field) {
      $other->set('field_terms', $term->id());
    }
    $other->save();

    for ($i = 0; $i < $count; $i++) {
      HsEntity::create([
        'bundle' => 'news_rss',
        'label' => 'News RSS feed',
        'field_url' => ['uri' => 'https://example.com/rss'],
        'field_terms' => $term->id(),
      ])->save();
    }

    $sandbox = [];
    $message = hs_entities_update_11001($sandbox);
    $storage = $this->container->get('entity_type.manager')->getStorage('hs_entity');
    if ($count > 50) {
      $this->assertLessThan(1, $sandbox['#finished']);
      $this->assertSame($count - 50, (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'news_rss')
        ->count()
        ->execute());
      $this->assertNotNull(HsEntityType::load('news_rss'));
      $this->assertNotNull(FieldStorageConfig::load('hs_entity.field_url'));
      $message = hs_entities_update_11001($sandbox);
    }

    $this->assertSame(1, $sandbox['#finished']);
    $this->assertStringContainsString("Removed $count News RSS entities", $message);
    $this->assertSame(0, (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'news_rss')
      ->count()
      ->execute());
    $this->assertNull(HsEntityType::load('news_rss'));
    $this->assertNull(FieldConfig::load('hs_entity.news_rss.field_terms'));
    $this->assertNull(FieldConfig::load('hs_entity.news_rss.field_url'));
    $this->assertNull(FieldStorageConfig::load('hs_entity.field_url'));
    $this->assertNull(EntityFormDisplay::load('hs_entity.news_rss.default'));
    $this->assertNull(EntityViewDisplay::load('hs_entity.news_rss.default'));
    $this->assertTrue($this->config('auto_entitylabel.settings.hs_entity.news_rss')->isNew());
    $this->assertTrue($this->container->get('module_handler')->moduleExists('hs_entities'));
    $this->assertNotNull(HsEntityType::load('other'));
    $retained_entity = HsEntity::load($other->id());
    $this->assertNotNull($retained_entity);
    $this->assertNotNull(Term::load($term->id()));

    if ($shared_field) {
      $this->assertNotNull(FieldStorageConfig::load('hs_entity.field_terms'));
      $this->assertTrue($retained_entity->hasField('field_terms'));
      $this->assertSame($term->id(), $retained_entity->get('field_terms')->target_id);
    }
    else {
      $this->assertNull(FieldStorageConfig::load('hs_entity.field_terms'));
    }

    $sandbox = [];
    $this->assertStringContainsString('Removed 0 News RSS entities', hs_entities_update_11001($sandbox));
    $this->assertSame(1, $sandbox['#finished']);
  }

  /**
   * Sites may have no feeds, many feeds, or reuse the taxonomy field storage.
   */
  public static function removalCases(): array {
    return [
      'empty bundle' => [0, FALSE],
      'multiple batches' => [51, FALSE],
      'shared field storage' => [51, TRUE],
    ];
  }

}
