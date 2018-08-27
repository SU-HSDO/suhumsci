<?php

namespace Drupal\Tests\hs_courses_importer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class HsCoursesImporterOverrideTest
 *
 * @coversDefaultClass \Drupal\hs_courses_importer\Overrides\CourseImporterOverrides
 * @package Drupal\Tests\hs_courses_importer\Functional
 * @group hs_courses_importer
 */
class HsCoursesImporterOverrideTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'user',
    'block',
    'hs_courses_importer',
  ];

  /**
   * The created user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Ignore strict schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Test url.
   *
   * @var string
   */
  protected $testUrl = 'http://explorecourses.stanford.edu/search?view=xml-20140630&academicYear=&page=0&q=AFRICAAM&filter-departmentcode-AFRICAAM=on&filter-coursestatus-Active=on&filter-term-Autumn=on';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);

    global $base_url;

    $this->config('hs_courses_importer.importer_settings')
      ->set('urls', [$this->testUrl])
      ->set('base_url', $base_url)
      ->save();
  }

  /**
   * Tests the migration config is overridden with correct data.
   *
   * @covers ::loadOverrides()
   * @covers ::getMigrationUrls()
   * @covers ::createConfigObject()
   * @covers ::getCacheSuffix()
   * @covers ::createConfigObject()
   */
  public function testConfigOverride() {
    $url = urlencode($this->testUrl);
    global $base_url;
    $url = "$base_url/api/hs_courses?feed=$url";

    $config = \Drupal::configFactory()
      ->get('migrate_plus.migration.hs_courses');

    $this->assertEquals($url, $config->get('source.urls.0'));
  }

}
