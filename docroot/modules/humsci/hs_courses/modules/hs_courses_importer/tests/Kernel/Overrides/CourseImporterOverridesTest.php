<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Overrides;

use Drupal\hs_courses_importer\Overrides\CourseImporterOverrides;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class CourseImporterOverridesTest.
 *
 * @coversDefaultClass \Drupal\hs_courses_importer\Overrides\CourseImporterOverrides
 * @group hs_courses_importer
 */
class CourseImporterOverridesTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'migrate_plus',
    'hs_courses_importer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config('hs_courses_importer.importer_settings')
      ->set('urls', ['http://explorecourses.stanford.edu/search?view=xml&q=abcdefg'])
      ->set('base_url', 'http://myurl.test')
      ->save();
    $this->config('migrate_plus.migration.hs_courses')
      ->set('source', ['urls' => ['teststring']])
      ->save();
  }

  /**
   * Test the configuration overrides function correctly.
   *
   * @covers ::__construct
   * @covers ::createConfigObject
   * @covers ::getCacheableMetadata
   * @covers ::getCacheSuffix
   * @covers ::loadOverrides
   * @covers ::getMigrationUrls
   */
  public function testImporterOverrides() {
    $url = \Drupal::configFactory()
      ->get('migrate_plus.migration.hs_courses')
      ->get('source.urls.0');

    $this->assertEquals('http://myurl.test/api/hs_courses?feed=' . urlencode('http://explorecourses.stanford.edu/search?view=xml&q=abcdefg'), $url);

    $overridder = new CourseImporterOverrides(\Drupal::configFactory());
    $this->assertNull($overridder->createConfigObject(''));
  }

}
