<?php

namespace Drupal\Tests\hs_events\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class HsEventsTest.
 *
 * @package Drupal\Tests\hs_events\Functional
 *
 * @group hs_events
 */
class HsEventsTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['block', 'hs_events'];

  /**
   * Disable strict config testing since entity_browser throws issues.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['bartik', 'seven']);

    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->container->get('config.factory');
    $config = $config_factory->getEditable('system.theme');
    $config->set('admin', 'seven');
    $config->set('default', 'bartik');
    $config->save();
    $config = $config_factory->getEditable('node.settings');
    $config->set('use_admin_theme', TRUE);
    $config->save();

    $this->drupalPlaceBlock('local_tasks_block', [
      'region' => 'content',
      'weight' => -20,
    ]);
    $this->drupalPlaceBlock('page_title_block', [
      'region' => 'content',
      'weight' => -50,
    ]);
  }

  /**
   * Tests the individual fields on the content type.
   */
  public function testHsEvents() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->drupalCreateUser([
      'administer nodes',
      'administer content types',
      'bypass node access',
      'access image_browser entity browser pages',
      'access file_browser entity browser pages',
      'access video_browser entity browser pages',
      'access media_browser entity browser pages',
      'dropzone upload files',
      'access media overview',
      'administer media',
      'administer eck entities',
      'bypass eck entity access',
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('node/add/stanford_event');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Create Event');

    $this->assertNotEmpty($assert_session->fieldExists('Show End Date'));
    $page->fillField('Title', $this->randomString());

    // Get the time rounded to the nearest 1/4 hour
    $time = round(time() / (15 * 60)) * (15 * 60);

    $date_fields = [
      'field_s_event_date[0][value][month]' => date('n', $time),
      'field_s_event_date[0][value][day]' => date('j', $time),
      'field_s_event_date[0][value][year]' => date('Y', $time),
      'field_s_event_date[0][value][hour]' => date('g', $time),
      'field_s_event_date[0][value][minute]' => (int) date('i', $time),
      'field_s_event_date[0][value][ampm]' => date('a', $time),
      'field_s_event_date[0][end_value][month]' => '',
      'field_s_event_date[0][end_value][day]' => '',
      'field_s_event_date[0][end_value][year]' => '',
      'field_s_event_date[0][end_value][hour]' => '',
      'field_s_event_date[0][end_value][minute]' => '',
      'field_s_event_date[0][end_value][ampm]' => '',
    ];

    foreach ($date_fields as $field => $value) {
      $assert_session->fieldValueEquals($field, $value);
    }

    // Tests the year is only a 20 year span
    $assert_session->optionNotExists('field_s_event_date[0][value][year]', date('Y') - 11);
    $assert_session->optionNotExists('field_s_event_date[0][value][year]', date('Y') + 11);
    $assert_session->optionExists('field_s_event_date[0][value][year]', date('Y') - 10);
    $assert_session->optionExists('field_s_event_date[0][value][year]', date('Y') + 10);
    $assert_session->optionNotExists('field_s_event_date[0][end_value][year]', date('Y') - 11);
    $assert_session->optionNotExists('field_s_event_date[0][end_value][year]', date('Y') + 11);
    $assert_session->optionExists('field_s_event_date[0][end_value][year]', date('Y') - 10);
    $assert_session->optionExists('field_s_event_date[0][end_value][year]', date('Y') + 10);
  }

}
