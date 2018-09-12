<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Plugin\migrare\process;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Class TranslateCourseTagTest.
 *
 * @covers \Drupal\hs_courses_importer\Plugin\migrate\process\TranslateCourseTag
 * @group hs_courses_importer
 */
class TranslateCourseTagTest extends EntityKernelTestBase {

  /**
   * Migrate plugin object.
   *
   * @var \Drupal\hs_courses_importer\Plugin\migrate\process\TranslateCourseTag
   */
  protected $tagTranslatePlugin;

  /**
   * Migrate process manager service.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processManager;

  /**
   * Course tag entity.
   *
   * @var \Drupal\hs_courses_importer\Entity\CourseTag
   */
  protected $courseTag;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'hs_courses_importer',
    'migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->processManager = $this->container->get('plugin.manager.migrate.process');

    $name = $this->randomMachineName();
    $this->courseTag = $this->entityManager->createInstance('hs_course_tag', [
      'id' => strtolower($name),
      'label' => $name,
      'tag' => $this->randomString(),
    ]);
    $this->courseTag->save();
  }

  /**
   * Test the translation works.
   */
  public function testTranslateTag() {
    /** @var \Drupal\hs_courses_importer\Plugin\migrate\process\TranslateCourseTag $translate_plugin */
    $translate_plugin = $this->processManager->createInstance('translate_course_tag');

    $row = new Row();
    $migrate = new MigrateExecutableTest();
    $translated = $translate_plugin->transform($this->courseTag->label(), $migrate, $row, '');
    $this->assertNotEquals($this->courseTag->label(), $translated);
    $this->assertEquals($this->courseTag->tag(), $translated);

    $this->courseTag->delete();
    $translated = $translate_plugin->transform($this->courseTag->label(), $migrate, $row, '');
    $this->assertEquals($this->courseTag->label(), $translated);

    $translate_plugin = $this->processManager->createInstance('translate_course_tag', ['ignore_empty' => TRUE]);
    $translated = $translate_plugin->transform($this->randomString(), $migrate, $row, '');
    $this->assertNull($translated);
  }

}

/**
 * Class MigrateExecutableTest for testing purposes.
 */
class MigrateExecutableTest implements MigrateExecutableInterface {

  /**
   * {@inheritdoc}
   */
  public function import() {

  }

  /**
   * {@inheritdoc}
   */
  public function rollback() {

  }

  /**
   * {@inheritdoc}
   */
  public function processRow(Row $row, array $process = NULL, $value = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function saveMessage($message, $level = MigrationInterface::MESSAGE_ERROR) {

  }

}
