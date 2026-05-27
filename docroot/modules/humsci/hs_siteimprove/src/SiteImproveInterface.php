<?php

namespace Drupal\hs_siteimprove;

/**
 * Defines an interface for the SiteImprove API.
 */
interface SiteImproveInterface {

  /**
   * Get the current site.
   *
   * @param bool $refresh
   *   Whether to refresh the site data from the API.
   *
   * @return object|null
   *   The current site.
   */
  public function getCurrentSite(bool $refresh = FALSE): ?object;

  /**
   * Get the current site ID.
   *
   * @param bool $refresh
   *   Whether to refresh the site data from the API.
   *
   * @return string|null
   *   The current site ID.
   */
  public function getCurrentSiteId(bool $refresh = FALSE): ?string;

  /**
   * Get the pages with broken links.
   *
   * @return array|null
   *   The pages with broken links.
   */
  public function getPagesWithBrokenLinks(): ?array;

}
