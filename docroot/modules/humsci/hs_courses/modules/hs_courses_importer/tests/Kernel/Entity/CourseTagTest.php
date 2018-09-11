<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class CourseTagTest
 *
 * @covers \Drupal\hs_courses_importer\Entity\CourseTag
 * @group hs_courses_importer
 * @group coverage
 */
class CourseTagTest extends EntityKernelTestBase {

  /**
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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $name = $this->randomMachineName();
    $this->courseTag = $this->entityManager->createInstance('hs_course_tag', [
      'id' => strtolower($name),
      'label' => $name,
      'tag' => 'test string',
    ]);
  }

  /**
   * Test the tag entity.
   */
  public function testTagEntity() {
    $this->assertEquals('test string', $this->courseTag->tag());
  }

}
