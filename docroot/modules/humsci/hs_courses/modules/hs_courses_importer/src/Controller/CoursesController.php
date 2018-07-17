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
   * @var \DOMDocument
   */
  protected $courseDom;

  /**
   * Constructs a new CoursesController object.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
    $this->courseDom = new \DOMDocument('1.0', 'UTF-8');
    $this->setCourseDom();
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
  public function courses() {
    $response = new Response();
    $response->setMaxAge(360);
    $response->headers->set('Content-Type', 'text/xml');
    $response->setContent($this->courseDom->saveXML());
    return $response;
  }

  /**
   * Set the Course Dom Property after getting and cleaning the data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function setCourseDom() {
    $url = 'http://explorecourses.stanford.edu/search?q=ARCHLGY&view=xml-20140630&collapse=&filter-departmentcode-ARCHLGY=on&filter-coursestatus-Active=on&filter-catalognumber-ARCHLGY=on';
    $config = $this->config('hs_courses_importer.importer_settings');
    if ($config_url = $config->get('url')) {
      $url = $config_url;
    }

    $api_response = $this->httpClient->request('GET', $url);
    $body = (string) $api_response->getBody();

    // The data from explorecourses.stanford.edu contains a ton of unwanted
    // markup that shouldnt be in an xml source. So lets clean it up first.
    $body = preg_replace("/[\t\n\r]/", ' ', $body);
    $body = preg_replace("/[[:blank:]]+/", " ", $body);
    $body = str_replace('> ', ">", $body);
    $body = str_replace(' <', "<", $body);
    $this->courseDom->loadXML($body);

    $this->cleanCourses();
    $this->setSectionGuids();
  }

  /**
   * Remove unwanted/unnecessary nodes.
   */
  protected function cleanCourses() {
    $remove_nodes = [
      'learningObjectives',
      'currentClassSize',
      'numEnrolled',
      'numWaitlist',
      'enrollStatus',
    ];
    $xpath = new \DOMXPath($this->courseDom);
    foreach ($remove_nodes as $node) {
      $elements = $xpath->query("//$node");
      foreach ($elements as $element) {
        // This is a hint from the manual comments
        $element->parentNode->removeChild($element);
      }
    }
  }

  /**
   * Set unique guids on each course section within the xml.
   *
   * If a course does not contain a section, create an empty section with only
   * a guid to identify it. This allows us to keep the migrate selector on the
   * sections/section.
   */
  protected function setSectionGuids() {
    $xpath = new \DOMXPath($this->courseDom);
    /** @var \SimpleXMLElement $all_sections [] */
    $all_sections = $xpath->query('//sections');

    /** @var \DOMElement $course_sections */
    foreach ($all_sections as $course_sections) {
      // Courses that have no sections, we'll add an empty section just for the
      // guid.
      if (!$course_sections->hasChildNodes()) {
        $child = new \DOMElement('section');
        $course_sections->appendChild($child);
      }

      // Build the guid parts.
      $course_id = $xpath->query('../administrativeInformation/courseId', $course_sections)[0]->textContent;
      $code = $xpath->query('../code', $course_sections)[0]->textContent;

      /** @var \DOMElement $section */
      foreach ($xpath->query('section', $course_sections) as $section) {
        $guid = "$course_id-$code";
        if ($xpath->query('classId', $section)->length) {
          $guid .= '-' . $xpath->query('classId', $section)[0]->textContent;
        }
        $guid = new \DOMElement('guid', $guid);
        $section->appendChild($guid);
      }
    }
  }

}
