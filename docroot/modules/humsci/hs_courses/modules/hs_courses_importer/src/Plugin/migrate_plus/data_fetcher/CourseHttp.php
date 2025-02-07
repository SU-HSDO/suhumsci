<?php

namespace Drupal\hs_courses_importer\Plugin\migrate_plus\data_fetcher;

use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: course_http
 *   data_fetcher_plugin: http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "course_http",
 *   title = @Translation("Stanford Course HTTP")
 * )
 */
class CourseHttp extends Http {

  /**
   * {@inheritdoc}
   */
  public function getResponseContent(string $url): string {
    $response = $this->getResponse($url);
    $body = $response->getBody();

    // The data from explorecourses.stanford.edu contains a ton of unwanted
    // markup that shouldn't be in an xml source. So lets clean it up first.
    $body = preg_replace("/[\t\n\r]/", ' ', $body);
    $body = preg_replace("/[[:blank:]]+/", " ", $body);
    $body = str_replace('> ', ">", $body);
    $body = str_replace(' <', "<", $body);

    $data = new \DOMDocument('1.0', 'UTF-8');;
    $data->loadXML($body);
    $this->cleanCourses($data);
    $this->setSectionGuids($data);

    return $data->saveXML();
  }

  /**
   * Remove unwanted/unnecessary nodes.
   */
  protected function cleanCourses($dom) {
    $remove_nodes = [
      'learningObjectives',
      'currentClassSize',
      'numEnrolled',
      'numWaitlist',
      'enrollStatus',
    ];
    $xpath = new \DOMXPath($dom);
    foreach ($remove_nodes as $node) {
      $elements = $xpath->query("//$node");
      foreach ($elements as $element) {
        // This is a hint from the manual comments.
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
  protected function setSectionGuids($dom) {
    $xpath = new \DOMXPath($dom);
    /* @var \SimpleXMLElement $all_sections [] */
    $all_sections = $xpath->query('//sections');

    /* @var \DOMElement $course_sections */
    foreach ($all_sections as $course_sections) {
      // Courses that have no sections, we'll add an empty section just for the
      // guid.
      if (!$course_sections->hasChildNodes()) {
        $child = new \DOMElement('section');
        $course_sections->appendChild($child);
      }

      // Build the guid parts.
      $course_id_element = $xpath->query('../administrativeInformation/courseId', $course_sections);
      $course_id = '000';

      // Some XML feeds from explore courses don't have the courseId element.
      // So we need to check for its existence.
      if ($course_id_element->length) {
        $course_id = $course_id_element->item(0)->textContent;
      }
      $code = $xpath->query('../code', $course_sections)->item(0)->textContent;

      /* @var \DOMElement $section */
      foreach ($xpath->query('section', $course_sections) as $section) {
        $guid = "$course_id-$code";
        if ($xpath->query('classId', $section)->length) {
          $class_id = $xpath->query('classId', $section)->item(0)->textContent;
          $guid .= "-$class_id";
        }
        $guid = new \DOMElement('guid', $guid);
        $section->appendChild($guid);
      }
    }
  }

}
