<?php

namespace Drupal\Test\hs_courses_importer\Unit\Entity;

use Drupal\hs_courses_importer\Entity\CourseTag;
use Drupal\Tests\UnitTestCase;

/**
 * Class CoursesControllerTest.
 *
 * @covers \Drupal\hs_courses_importer\Entity\CourseTag
 * @group hs_courses_importer
 */
class CourseTagTest extends UnitTestCase {

  /**
   * Test the Course controller methods.
   */
  public function testCourseController() {
    $tag_string = $this->getRandomGenerator()->string();
    $tag = new CourseTag([
      'id' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
      'tag' => $tag_string,
    ], 'hs_course_tag');
    $this->assertEquals($tag_string, $tag->tag());
  }

}
