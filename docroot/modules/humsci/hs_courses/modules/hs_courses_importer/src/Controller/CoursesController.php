<?php

namespace Drupal\hs_courses_importer\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CoursesController.
 */
class CoursesController extends ControllerBase {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new CoursesController object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Getcourses.
   *
   * @return string
   *   Return Hello string.
   */
  public function getCourses() {
    $url = 'http://explorecourses.stanford.edu/search?q=ARCHLGY&view=xml-20140630&collapse=&filter-departmentcode-ARCHLGY=on&filter-coursestatus-Active=on&filter-catalognumber-ARCHLGY=on';
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');

    $config = $this->config('hs_courses_importer.importer_settings');
    if ($config_url = $config->get('url')) {
      $url = $config_url;
    }
    $courses = $this->getCoursesFromUrl($url);
    $courses = $this->processCourses($courses);
    $this->cleanArrays($courses);

    $response->setContent(json_encode($courses));

    return $response;
  }

  /**
   * Clear empty arrays to prevent strange behaviors with field mappings.
   *
   * @param mixed $data
   */
  protected function cleanArrays(&$data) {
    if (is_array($data)) {
      if (!empty(array_filter($data))) {
        foreach ($data as &$value) {
          $this->cleanArrays($value);
        }
      }
      $data = array_filter($data);
    }
  }

  protected function getCoursesFromUrl($url) {
    $hashed_url = md5($url);
    $cache = \Drupal::cache()->get("hs_courses:$hashed_url");
    if ($cache) {
      return $cache->data;
    }

    $api_response = $this->httpClient->request('GET', $url);
    if ($api_response->getStatusCode() != 200) {
      return [];
    }

    $xml = simplexml_load_string((string) $api_response->getBody());
    $courses = json_decode(json_encode($xml), TRUE);

    \Drupal::cache()->set("hs_courses:$hashed_url", $courses);
  }

  /**
   * Modify the courses data array to get the structure we need.
   *
   * @param array $courses
   *   Original data from explorecourses.stanford.edu.
   *
   * @return array
   *   Cleaned up data.
   */
  protected function processCourses(array $courses) {
    $return_courses = [];

    foreach ($courses['courses']['course'] as $course) {
      unset($course['learningObjectives']);
      if (!empty($course['tags']['tag'][0])) {
        $course['tags'] = $course['tags']['tag'];
      }

      $return_course = $course;

      $guid = $course['administrativeInformation']['courseId'];
      $guid .= '-' . $course['code'];
      $return_course['guid'] = $guid;

      foreach ($course['sections'] as $sections) {
        unset($return_course['sections']);
        $return_course[$guid] = $guid;

        if (!is_array($sections)) {
          $return_courses[$guid] = $return_course;
          continue;
        }

        // Only one section on the course.
        if (isset($sections['classId'])) {
          $sections = [$sections];
        }

        // Multiple sections on the course.
        foreach ($sections as $section) {
          unset($section['numEnrolled'], $section['maxEnrolled'], $section['numWaitlist'], $section['enrollStatus'], $section['currentClassSize'], $section['currentWaitlistSize']);
          $section_guid = $guid . '-' . $section['classId'];
          if (!empty($section['schedules']['schedule'][0])) {
            $section['schedules']['schedule'] = $section['schedules']['schedule'][0];
          }
          $section['schedule'] = $section['schedules']['schedule'];
          unset($section['schedules']);


          if (!empty($section['schedule']['instructors']['instructor'][0])) {
            $section['schedule']['instructors'] = $section['schedule']['instructors']['instructor'];
          }


          $return_course['section'] = $section;
          $return_course['guid'] = $section_guid;
          $return_courses[$section_guid] = $return_course;
        }
      }
    }
    return $return_courses;
  }

}
