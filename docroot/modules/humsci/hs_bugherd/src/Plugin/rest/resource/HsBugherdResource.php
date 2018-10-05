<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;

/**
 * Class BugherdResource.
 *
 * Keeping this class just to prevent upgrade breaking.
 *
 * @RestResource(
 *   id = "hs_bugherd_resource",
 *   label = @translation("HS Bugherd Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/hs-bugherd",
 *     "https://www.drupal.org/link-relations/create" = "/api/hs-bugherd"
 *   }
 * )
 * @deprecated use \Drupal\hs_bugherd\Plugin\rest\resource\BugherdResource and
 * \Drupal\hs_bugherd\Plugin\rest\resource\JiraResource instead.
 */
class HsBugherdResource extends ResourceBase {

}
