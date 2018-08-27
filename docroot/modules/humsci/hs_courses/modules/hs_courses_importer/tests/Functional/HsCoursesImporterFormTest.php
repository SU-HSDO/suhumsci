<?php

namespace Drupal\Tests\hs_courses_importer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test Course Importer forms.
 *
 * @coversDefaultClass \Drupal\hs_courses_importer\Form\CourseImporter
 * @group hs_courses_importer
 */
class HsCoursesImporterFormTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test form validation and submission.
   *
   * @covers ::validateForm()
   * @covers ::validateIsUrl()
   * @covers ::validateIsExploreCourses()
   * @covers ::validateIsXmlUrl()
   * @covers ::submitForm()
   */
  public function testFormSubmission() {
    $urls = 'garbage';
    $this->drupalPostForm('/admin/structure/migrate/course-importer', ['urls' => $urls], 'Save configuration');
    $this->assertSession()->pageTextContains('Invalid URL Format');

    $urls = 'http://google.com/maps?q=art';
    $this->drupalPostForm('/admin/structure/migrate/course-importer', ['urls' => $urls], 'Save configuration');
    $this->assertSession()
      ->pageTextContains('Must be for explorecourses.stanford.edu');

    $urls = 'http://explorecourses.stanford.edu/search?q=art';
    $this->drupalPostForm('/admin/structure/migrate/course-importer', ['urls' => $urls], 'Save configuration');
    $this->assertSession()->pageTextContains('URL Must be an XML feed');

    $urls = "http://explorecourses.stanford.edu/search?view=xml-20140630&academicYear=&page=0&q=AFRICAAM&filter-departmentcode-AFRICAAM=on&filter-coursestatus-Active=on&filter-term-Autumn=on";
    $this->drupalPostForm('/admin/structure/migrate/course-importer', ['urls' => $urls], 'Save configuration');
    $this->assertSession()
      ->pageTextContains('The configuration options have been saved');

    $config = $this->config('hs_courses_importer.importer_settings');
    $this->assertNotEmpty($config->get('urls'));

    $config = \Drupal::configFactory()
      ->get('migrate_plus.migration.hs_courses');

    $migration_urls = $config->get('source.urls');
    $this->assertNotEmpty($migration_urls);
    $migration_url = urldecode($migration_urls[0]);

    $this->assertNotFalse(strpos($migration_url, 'feed='));
    $this->assertEquals($urls, substr($migration_url, strpos($migration_url, 'feed=') + 5));

    $this->drupalGet('/api/hs_courses', ['query' => ['feed' => $urls]]);
    $this->assertSession()->responseContains('<course>');
  }

}
