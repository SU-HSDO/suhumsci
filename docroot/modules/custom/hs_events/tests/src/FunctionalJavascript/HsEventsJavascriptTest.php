<?php

namespace Drupal\Tests\hs_events\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

class HsEventsJavascriptTest extends JavascriptTestBase {

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
   * Log in with appropriate permissions.
   */
  public function testNodeAccess() {
    $assert_session = $this->assertSession();
    $this->drupalGet('node/add/stanford_event');
    $assert_session->statusCodeEquals(403);

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
  }

  /**
   * Tests the content type.
   */
  public function testHsEvents() {
    $this->testNodeAccess();
    $test_image = $this->createImageMedia();
    $this->createVideoMedia();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/stanford_event');

    $page->checkField('Show End Date');

    $field_tabs = [
      'Event Details' => [
        'text' => [
          'title[0][value]' => 'Test Event',
          'body[0][summary]' => NULL,
          'body[0][value]' => 'Body Value',
          'field_s_event_link[0][uri]' => 'http://google.com',
          'field_s_event_link[0][title]' => 'Google',
        ],
        'select' => [
          'field_s_event_date[0][value][month]' => date('n'),
          'field_s_event_date[0][value][day]' => date('j'),
          'field_s_event_date[0][value][year]' => date('Y'),
          'field_s_event_date[0][value][hour]' => date('g'),
          'field_s_event_date[0][value][minute]' => 15,
          'field_s_event_date[0][value][ampm]' => date('a'),
          'field_s_event_date[0][end_value][month]' => date('n'),
          'field_s_event_date[0][end_value][day]' => date('j'),
          'field_s_event_date[0][end_value][year]' => date('Y') + 1,
          'field_s_event_date[0][end_value][hour]' => date('g'),
          'field_s_event_date[0][end_value][minute]' => 15,
          'field_s_event_date[0][end_value][ampm]' => date('a'),
        ],
      ],
      'Supplement Info' => [
        'text' => [
          'field_s_event_location[0][value]' => 'The White House',
          'field_s_event_map_link[0][uri]' => 'http://maps.google.com',
          'field_s_event_map_link[0][title]' => 'Google Maps',
          'field_s_event_sponsor[0][value]' => 'Stanford University',
          'field_s_event_contact_email[0][value]' => 'test@test.com',
          'field_s_event_contact_phone[0][value]' => '123-456-7890',
          'field_s_event_admission[0][value]' => 'Free',
        ],
        'autocomplete' => [
          'field_s_event_category[0][target_id]' => 'Test Category',
          'field_s_event_audience[0][target_id]' => 'General Public',
        ],
      ],
    ];

    // Check for each field on the node form.
    foreach ($field_tabs as $tab => $field_types) {
      $page->clickLink($tab);

      foreach ($field_types as $type => $fields) {
        // Check for the fields and populate them.
        foreach ($fields as $name => $value) {
          $this->assertTrue($assert_session->fieldExists($name));

          if (!is_null($value)) {

            switch ($type) {
              case 'select':
                $page->selectFieldOption($name, $value);
                break;
              default:
                $page->fillField($name, $value);
                break;
            }
          }
        }
      }
    }

    // EVENT DETAILS.
    $page->clickLink('Event Details');
    $page->find('css', '.field--name-field-s-event-image summary')->click();
    $this->getSession()->switchToIFrame('entity_browser_iframe_image_browser');

    $assert_session->waitForId('views-exposed-form-media-entity-browser-image-browser');
    $assert_session->pageTextContains('Image Browser');
    $assert_session->fieldExists('name');
    $page->findLink('Embed a File')->click();
    $assert_session->waitForField('upload[uploaded_files]');
    $assert_session->pageTextContains('Select Files');
    $page->findLink('Media Library')->click();
    $assert_session->waitForField('Name');

    $page->find('css', 'img[src*="/' . $test_image . '"]')->click();

    $assert_session->waitForElementVisible('css', 'input[name="use_selected"]')
      ->click();

    $this->getSession()->switchToIFrame();
    $assert_session->waitForElementVisible('css', '.field--name-field-s-event-image input[name="remove"]');

    $page->pressButton('Add new Speaker');
    $assert_session->waitForField('field_s_event_speaker[form][inline_entity_form][title][0][value]');
    $page->fillField('field_s_event_speaker[form][inline_entity_form][title][0][value]', $this->randomString());

    $page->clickLink('Post Event Details');

    // Switch into video browser.
    $this->getSession()->switchToIFrame('entity_browser_iframe_video_browser');
    $page->checkField('Select this item');
    $page->pressButton('Continue');
    $assert_session->waitForElementVisible('css', 'input[name="use_selected"]')
      ->click();

    $this->getSession()->switchToIFrame();
    $assert_session->waitForElement('css', '.field--name-field-s-event-video input[name="remove"]');

    $page->pressButton('Save');
    // Valdates the path auto works.
    $assert_session->addressEquals('/events/test-event');
  }

  /**
   * Upload and create a media item to be used on events.
   *
   * @return string
   *   Name of the media item.
   */
  protected function createImageMedia() {
    $test_filename = $this->randomMachineName() . '.png';
    $test_filepath = 'public://' . $test_filename;
    file_put_contents($test_filepath, file_get_contents(__DIR__ . '/avatar.png'));
    $source_field_id = 'field_media_image';
    $this->drupalGet("media/add/image");

    $this->getSession()->getPage()->fillField('Name', $test_filename);
    $real_path = \Drupal::service('file_system')->realpath($test_filepath);
    $this->getSession()->getPage()
      ->attachFileToField("files[{$source_field_id}_0]", $real_path);

    $result = $this->assertSession()->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()
      ->pageTextContains("Image $test_filename has been created");
    return $test_filename;
  }

  /**
   * Create a media video item to be used on events.
   *
   * @return string
   *   Name of the media item.
   */
  protected function createVideoMedia() {
    $name = $this->randomMachineName();
    $video_url = 'https://www.youtube.com/watch?v=uLcS7uIlqPo';
    $source_field_id = 'field_media_video_embed_field';
    $this->drupalGet("media/add/video");
    $this->getSession()->getPage()->fillField('Name', $name);
    $this->getSession()
      ->getPage()
      ->fillField("{$source_field_id}[0][value]", $video_url);
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains("video $name has been created");
    return $name;
  }

}
