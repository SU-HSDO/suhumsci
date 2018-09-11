<?php

namespace Drupal\Test\hs_courses_importer\Unit\Controller;

use Drupal\hs_courses_importer\Controller\CoursesController;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CoursesControllerTest.
 *
 * @covers \Drupal\hs_courses_importer\Controller\CoursesController
 * @group hs_courses_importer
 * @group coverage
 */
class CoursesControllerTest extends UnitTestCase {

  /**
   * Test the Course controller methods.
   */
  public function testCourseController() {

    $request_stack = new RequestStack();
    $request = new Request();
    $request_stack->push($request);
    $course_controller = new CoursesController($this->getClient(), $request_stack);
    $this->assertEmpty(preg_grep('/<course/', explode("\n", $course_controller->courses()
      ->getContent())));

    $request_stack = new RequestStack();
    $request = new Request(['feed' => 'http://example.com/api-endpoint']);
    $request_stack->push($request);
    $course_controller = new CoursesController($this->getClient(), $request_stack);

    $this->assertNotEmpty(preg_grep('/<course/', explode("\n", $course_controller->courses()
      ->getContent())));
  }

  /**
   * Get the guzzle client.
   *
   * @return object
   *   Client.
   */
  protected function getClient() {
    $client = $this->prophesize(ClientInterface::class);
    $client->request('GET', 'http://example.com/api-endpoint', Argument::any())
      ->will(function () {
        return new Response(200, ['Content-Type' => 'text/xml'], file_get_contents(__DIR__ . '/courses.xml'));
      });
    return $client->reveal();
  }

}
