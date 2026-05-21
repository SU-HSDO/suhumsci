<?php

namespace Drupal\Test\hs_courses_importer\Kernel;

use Drupal\Component\Uuid\Php;
use Drupal\hs_courses_importer\CourseTagListBuilder;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class CourseTagListBuilderTest.
 */
#[CoversClass(CourseTagListBuilder::class)]
#[Group('hs_courses_importer')]
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
  protected static $modules = ['system', 'hs_courses_importer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->listBuilder = $this->entityTypeManager->getListBuilder('hs_course_tag');
  }

  /**
   * Test the list builder header and row builder.
   */
  public function testListBuilder() {
    $header = $this->listBuilder->buildHeader();
    $this->assertArrayHasKey('label', $header);
    $this->assertArrayHasKey('tag', $header);
    $uuid = new Php();
    $entity = $this->entityTypeManager->createInstance('hs_course_tag', [
      'uuid' => $uuid->generate(),
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'tag' => $this->randomString(),
    ]);
    $entity->save();

    $row = $this->listBuilder->buildRow($entity);
    $this->assertArrayHasKey('label', $row);
    $this->assertArrayHasKey('tag', $row);
  }

}
