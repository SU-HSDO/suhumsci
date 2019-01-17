<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Tests\hs_courses_importer\Kernel\HsCoursesImporterTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

/**
 * Class HsCoursesImporterFormTest.
 *
 * @covers \Drupal\hs_courses_importer\Form\CourseImporter
 * @group hs_courses_importer
 */
class CourseImporterTest extends HsCoursesImporterTestBase implements ServiceModifierInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Namespace of the form.
   *
   * @var string
   */
  protected $formClass = '\Drupal\hs_courses_importer\Form\CourseImporter';

  /**
   * A valid testable URL.
   *
   * @var string
   */
  protected $validUrl = 'http://explorecourses.stanford.edu/search?view=xml&q=abcdefg';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->formBuilder = $this->container->get('form_builder');
    $client = $this->prophesize(Client::class);

    $client->request(Argument::is('GET'), Argument::is('garbage url'))
      ->willReturn(new Response());
    $client->request(Argument::is('GET'), Argument::is('http://google.com'))
      ->willReturn(new Response());
    $client->request(Argument::is('GET'), Argument::is('http://explorecourses.stanford.edu/search&view=xml-20140630'))
      ->willReturn(new Response(200, ['Content-Type' => 'text/html']));
    $client->request(Argument::is('GET'), Argument::is($this->validUrl))
      ->willReturn(new Response(200, ['Content-Type' => 'text/xml']));

    $this->container->set('http_client', $client->reveal());
  }

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/project/drupal/issues/2571475#comment-11938008
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('test.http_client.middleware');
  }

  /**
   * Test the form class and its methods.
   */
  public function testForm() {
    $form = $this->formBuilder->getForm($this->formClass);
    $this->assertCount(4, Element::children($form));
    $this->assertArrayHasKey('urls', $form);

    $form_state = new FormState();
    $form_state->setValue('urls', 'garbage url');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', 'http://google.com');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', 'http://explorecourses.stanford.edu/search');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', $this->validUrl);
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertEmpty($form_state->getErrors());

    $config_urls = $this->config('hs_courses_importer.importer_settings')
      ->get('urls');
    $this->assertEquals($this->validUrl, reset($config_urls));
  }

}
