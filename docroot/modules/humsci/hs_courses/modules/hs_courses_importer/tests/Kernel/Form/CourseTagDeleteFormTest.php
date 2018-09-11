<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class HsCoursesImporterFormTest.
 *
 * @covers \Drupal\hs_courses_importer\Form\CourseTagDeleteForm
 * @group hs_courses_importer
 * @group coverage
 */
class CourseTagDeleteFormTest extends EntityKernelTestBase {

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
      'tag' => $this->randomString(),
    ]);
  }

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
  }

}
