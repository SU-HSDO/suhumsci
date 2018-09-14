<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\hs_courses_importer\Entity\CourseTag;
use Drupal\Tests\hs_courses_importer\Kernel\HsCoursesImporterTestBase;

/**
 * Class HsCoursesImporterFormTest.
 *
 * @covers \Drupal\hs_courses_importer\Form\CourseTagDeleteForm
 * @group hs_courses_importer
 */
class CourseTagDeleteFormTest extends HsCoursesImporterTestBase {

  /**
   * Test the form class and its methods.
   */
  public function testForm() {
    /** @var \Drupal\hs_courses_importer\Form\CourseTagDeleteForm $form_object */
    $form_object = $this->entityManager->getFormObject('hs_course_tag', 'delete');
    $form_object->setEntity($this->courseTag);
    $this->assertEquals("Are you sure you want to delete {$this->courseTag->label()}?", strip_tags($form_object->getQuestion()
      ->render()));

    $this->assertEquals('admin/structure/migrate/hs_course_tag', $form_object->getCancelUrl()
      ->getInternalPath());
    $this->assertEquals('Delete', $form_object->getConfirmText()->render());
    $form_state = new FormState();
    $form = [];
    $form = $form_object->form($form, $form_state);
    $form_object->submitForm($form, $form_state);

    $this->assertEmpty(CourseTag::load($this->courseTag->id()));
  }

}
