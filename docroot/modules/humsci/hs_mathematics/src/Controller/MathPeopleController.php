<?php

namespace Drupal\hs_mathematics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class MathPeopleController.
 */
class MathPeopleController extends ControllerBase {

  /**
   * Get the json data.
   *
   * @return JsonResponse
   *   Return json response.
   */
  public function get() {
    $people = $this->getCsvData();
    foreach ($people as &$person) {
      $person['uid'] = md5($person['FirstName'] . $person['LastName']);
      $person['Affiliation'] = explode(',', $person['Affiliation']);
      $person['FacultyAdvisor'] = explode(',', $person['FacultyAdvisor']);
      $person['FacultyType'] = explode(',', $person['FacultyType']);
      $person['ResearchAreas'] = explode(',', $person['ResearchAreas']);
      $person['StaffType'] = explode(',', $person['StaffType']);
    }
    return new JsonResponse($people);
  }

  /**
   * Get data from the csv as an array.
   *
   * @return array
   */
  protected function getCsvData() {

    $data = [];
    $headers = [];
    $file = fopen(__DIR__ . '/math.csv', 'r');
    while ($row = fgetcsv($file)) {
      if (empty($header)) {
        $headers = $row;
        foreach ($headers as &$header) {
          $header = str_replace(' ', '', ucwords($header));
          $header = preg_replace("/[^a-zA-Z]/", '', $header);
        }
        continue;
      }

      $data[] = array_combine($headers, $row);
    }
    fclose($file);
    return $data;
  }

}
