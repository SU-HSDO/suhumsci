<?php

namespace Drupal\Tests\react_paragraphs\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class ReactParagraphsTest.
 *
 * @package Drupal\Tests\react_paragraphs\Functional
 */
class ReactParagraphsTest extends WebDriverTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'react_paragraphs',
    'paragraphs',
    'node',
    'field',
    'entity_reference_revisions',
    'text',
    'entity_browser',
    'text',
    'link',
    'color_field',
    'viewfield',
    'webform',
    'ds',
    'field_formatter_class',
    'hs_field_helpers',
    'layout_builder',
    'stanford_media',
    'allowed_formats',
    'paragraphs',
    'media',
    'field_permissions',
    'views',
    'dblog',
    'options',
  ];

  /**
   * Created node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('module_installer')
      ->install(['test_react_paragraphs']);
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic Page']);

    // Create a field.
    FieldStorageConfig::create([
      'field_name' => 'field_react_paragraphs',
      'type' => 'react_paragraphs',
      'entity_type' => 'node',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_react_paragraphs',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'React Paragraphs',
    ])->save();

    $node_form = EntityFormDisplay::load('node.page.default');
    $node_form->setComponent('field_react_paragraphs', ['weight' => -99]);
    $node_form->removeComponent('body');
    $node_form->save();

    $node_display = EntityViewDisplay::load('node.page.default');
    $node_display->setComponent('field_react_paragraphs');
    $node_display->save();

    $field_values = [];
    $this->addParagraph($field_values, 'hs_text_area', 6, 0, 0);
    $this->addParagraph($field_values, 'hs_text_area', 6, 0, 1);

    $this->addParagraph($field_values, 'hs_postcard', 4, 1, 0);
    $this->addParagraph($field_values, 'hs_postcard', 4, 1, 1);
    $this->addParagraph($field_values, 'hs_postcard', 4, 1, 2);

    $this->addParagraph($field_values, 'hs_text_area', 6, 2, 0);
    $this->addParagraph($field_values, 'hs_postcard', 6, 2, 1);

    $this->node = Node::create([
      'type' => 'page',
      'title' => 'Test Node',
      'field_react_paragraphs' => $field_values,
    ]);
    $this->node->save();
  }

  /**
   * Add a new paragraph to field values.
   *
   * @param array $field_values
   *   Current field values.
   * @param string $paragraph_type
   *   Paragraph bundle machine name.
   * @param int $width
   *   Width of the item 2 - 12.
   * @param int $row
   *   Index of the row starting at 0.
   * @param int $index
   *   Index of the item in the row starting at 0.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function addParagraph(array &$field_values, $paragraph_type, $width = 12, $row = 0, $index = 0) {
    switch ($paragraph_type) {
      case 'hs_postcard':
        $paragraph = $this->getPostcardParagraph();
        break;

      default:
        $paragraph = $this->getTextAreaParagraph();
        break;
    }

    $field_values[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
      'settings' => json_encode([
        'width' => $width,
        'row' => $row,
        'index' => $index,
      ]),
    ];
  }

  /**
   * Get a text area paragraph entity.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   Created paragraph entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getTextAreaParagraph() {
    $paragraph = Paragraph::create([
      'type' => 'hs_text_area',
      'field_hs_text_area' => [
        'value' => $this->randomString(rand(10, 150)),
        'format' => '',
      ],
    ]);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Get a postcard paragraph entity.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   Created paragraph entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getPostcardParagraph() {
    $paragraph = Paragraph::create([
      'type' => 'hs_postcard',
      'field_hs_postcard_title' => $this->randomString(),
      'field_hs_postcard_body' => [
        'value' => $this->randomString(rand(10, 50)),
        'format' => '',
      ],
    ]);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Test the react field functions and saves correctly.
   */
  public function testReactField() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet("/node/{$this->node->id()}/edit");
    $this->assertSession()
      ->waitForElement('css', 'div[data-react-beautiful-dnd-droppable]');

    $this->assertSession()->pageTextContains('Content Toolbox');
    $this->assertSession()->pageTextContains('Accordion');
    $this->assertSession()->pageTextContains('Hero Image');
    $this->assertSession()->pageTextContains('Postcard');
    $this->assertSession()->pageTextContains('Text Area');
    $this->assertSession()->pageTextContains('View');
    $this->assertSession()->pageTextContains('Webform');
    $this->assertSession()->buttonExists('Add Another Row');

    $this->assertSession()
      ->elementsCount('css', '.react-paragraphs-widget .row', 3);

    $this->assertSession()
      ->elementsCount('css', '.react-paragraphs-widget .item', 7);

    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', '.react-paragraphs-widget .row');
    $this->assertSession()->elementsCount('css', '.item', 2, $rows[0]);
    $this->assertSession()->elementsCount('css', '.item', 3, $rows[1]);
    $this->assertSession()->elementsCount('css', '.item', 2, $rows[2]);

    $this->assertSession()
      ->waitForElementVisible('css', '.test-wait', 1 * 1000);

    /** @var \Behat\Mink\Element\NodeElement $postcard */
    $postcard = $rows[1]->find('css', '.item-summary');
    /** @var \Behat\Mink\Element\NodeElement $last_row_destination */
    $last_row_destination = $rows[2]->find('css', '.item-list');
    $postcard->dragTo($last_row_destination);
  }

}
