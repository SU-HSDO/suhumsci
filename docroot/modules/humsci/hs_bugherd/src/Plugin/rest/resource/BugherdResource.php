<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\hs_bugherd\Entity\BugherdConnection;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Class BugherdResource.
 *
 * @RestResource(
 *   id = "hs_bugherd_resource",
 *   label = @translation("HS Bugherd Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/hs-bugherd",
 *     "https://www.drupal.org/link-relations/create" = "/api/hs-bugherd"
 *   }
 * )
 */
class BugherdResource extends ResourceBase {

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   Post data from API.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Returned responses.
   */
  public function post(array $data) {
    $bugherd_connections = BugherdConnection::loadMultiple();

    // Data from jira has this key. Bugherd does not.
    if (isset($data['webhookEvent'])) {
      /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
      foreach ($bugherd_connections as $connection) {
        if ($connection->getJiraProject() == $data['issue']['fields']['project']['key']) {
          return new ResourceResponse($connection->updateBugherdTicket($data));
        }
      }

      return new ResourceResponse($this->t('Not applicable Jira Data'));
    }

    /** @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface $connection */
    foreach ($bugherd_connections as $connection) {
      if ($connection->getBugherdProject() == $data['task']['project_id']) {
        return new ResourceResponse($connection->updateJiraTicket($data));
      }
    }
    return new ResourceResponse($this->t('Not applicable Bugherd Data'));
  }

}
