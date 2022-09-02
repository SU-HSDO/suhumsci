<?php

namespace Drupal\humsci_events_listeners\EventSubscriber;

/**
 * Event listeners trait.
 */
trait HsEventListenersTrait {

  /**
   * Lookup an internal path.
   *
   * @param string $uri
   *   The destination path.
   *
   * @return string|null
   *   The internal path, or NULL if not found.
   */
  protected static function lookupInternalPath(string $uri): ?string {
    // If a redirect is added to go to the aliased path of a node (often from
    // importing redirect), change the destination to target the node instead.
    // This works if the destination is `/about` or `/node/9`.
    if (preg_match('/^internal:(\/.*)/', $uri, $matches)) {
      // Find the internal path from the alias.
      $path = \Drupal::service('path_alias.manager')
        ->getPathByAlias($matches[1]);

      // Grab the node id from the internal path and use that as destination.
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        return 'entity:node/' . $matches[1];
      }
    }
    return NULL;
  }

}
