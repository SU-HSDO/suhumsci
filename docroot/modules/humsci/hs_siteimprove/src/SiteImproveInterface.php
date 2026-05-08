<?php

namespace Drupal\hs_siteimprove;

/**
 * Defines an interface for the SiteImprove API.
 */
interface SiteImproveInterface {

  /**
   * Get the current site.
   *
   * @return object
   *   The current site.
   */
  public function getCurrentSite(): ?object;

  /**
   * Get the current site ID.
   *
    * @return string|null
   *   The current site ID.
   */
  public function getCurrentSiteId(): ?string;

  /**
   * Get the pages with broken links.
   *
   * @return array
   *   The pages with broken links.
   */
  public function getPagesWithBrokenLinks(): ?array;

}
