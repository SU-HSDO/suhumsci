<?php

namespace Drupal\Test\hs_courses_importer\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class CourseTagListBuilderTest.
 *
 * @covers \Drupal\hs_courses_importer\CourseTagListBuilder
 * @group hs_courses_importer
 */
class CourseTagListBuilderTest extends EntityKernelTestBase {

  /**
   * Tag List builder object.
   *
   * @var \Drupal\hs_courses_importer\CourseTagListBuilder
   */
  protected $listBuilder;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'hs_courses_importer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->listBuilder = $this->entityManager->getListBuilder('hs_course_tag');
  }

  /**
   * Test the list builder header and row builder.
   */
  public function testListBuilder() {
    $header = $this->listBuilder->buildHeader();
    $this->assertArrayHasKey('label', $header);
    $this->assertArrayHasKey('id', $header);
    $entity = $this->entityManager->createInstance('hs_course_tag', [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'tag' => $this->randomString(),
    ]);
    $entity->save();

    $row = $this->listBuilder->buildRow($entity);
    $this->assertArrayHasKey('label', $row);
    $this->assertArrayHasKey('id', $row);
  }

}
