<?php

namespace Drupal\Test\hs_courses_importer\Kernel\Controller;

use Drupal\hs_courses_importer\Controller\CoursesController;
use Drupal\Tests\hs_courses_importer\Kernel\HsCoursesImporterTestBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CoursesControllerTest.
 *
 * @covers \Drupal\hs_courses_importer\Controller\CoursesController
 * @group hs_courses_importer
 */
class CoursesControllerTest extends HsCoursesImporterTestBase {

  /**
   * Test the Course controller methods.
   */
  public function testCourseController() {
    $course_controller = CoursesController::create($this->container);
    $this->getClient();
    $this->assertEmpty(preg_grep('/<course/', explode("\n", $course_controller->courses()
      ->getContent())));

    $request = new Request(['feed' => 'http://example.com/api-endpoint']);
    \Drupal::requestStack()->push($request);
    $course_controller = new CoursesController($this->getClient(), \Drupal::requestStack());

    $courses = $course_controller->courses()->getContent();
    $this->assertContains('<course', $courses);
    $this->assertContains('<guid>217965-16SI</guid>', $courses);
    $this->assertContains('<guid>220800-20-30648</guid>', $courses);
    $this->assertNotContains('<learningObjectives', $courses);
    $this->assertNotContains('<currentClassSize', $courses);
    $this->assertNotContains('<numEnrolled', $courses);
    $this->assertNotContains('<numWaitList', $courses);
    $this->assertNotContains('<enrollStatus', $courses);
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
