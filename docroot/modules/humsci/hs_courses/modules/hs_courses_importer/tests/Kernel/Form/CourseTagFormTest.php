<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class HsCoursesImporterFormTest.
 *
 * @covers \Drupal\hs_courses_importer\Form\CourseTagForm
 * @group hs_courses_importer
 * @group coverage
 */
class CourseTagFormTest extends EntityKernelTestBase {

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
    $this->installEntitySchema('hs_course_tag');
  }

  /**
   * Test the form class and its methods.
   */
  public function testForm() {
    $name = strtolower($this->randomMachineName());
    $tag_value = $this->randomString();
    $tag = $this->entityManager->createInstance('hs_course_tag', [
      'id' => $name,
      'label' => $name,
      'tag' => $tag_value,
    ]);

    /** @var \Drupal\hs_courses_importer\Form\CourseTagForm $form_object */
    $form_object = $this->entityManager->getFormObject('hs_course_tag', 'add');
    $form = [];
    $form_state = new FormState();
    $form_object->setEntity($tag);
    $form = $form_object->form($form, $form_state);

    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('id', $form);
    $this->assertArrayHasKey('tag', $form);

    $form_state->setValues([
      'label' => $name,
      'id' => $name,
      'tag' => $tag_value,
    ]);

    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);

    $this->assertEquals($tag_value, $this->config('hs_courses_importer.hs_course_tag.' . $name)->get('tag'));
  }

}
