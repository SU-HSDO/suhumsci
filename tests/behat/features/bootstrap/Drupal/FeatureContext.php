<?php

namespace Drupal;

use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * FeatureContext class defines custom step definitions for Behat.
 */
class FeatureContext extends RawDrupalContext {

  public $visited_links = [];

  /**
   * Every scenario gets its own context instance.
   *
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {

  }

  /**
   * @Then every link in the :region( region) should work
   *
   *
   * Modified version of a gist to loop through all links in a region and
   * validate their 200 response.
   *
   * @see https://gist.github.com/jonpugh/b2f95dd8e89b3218a20a
   */
  public function everyLinkShouldWork($region) {
    $regionObj = $this->getRegion($region);
    $elements = $regionObj->findAll('xpath', '//a/@href');
    $count = 0;
    foreach ($elements as $element) {
      // If element or tag is empty...
      if (empty($element->getParent())) {
        continue;
      }
      $href = $element->getParent()->getAttribute('href');
      // Skip if empty
      if (!$this->checkLink($href)) {
        continue;
      }
      $count++;

      print "Checking Link: " . $href . "\n";
      // Mimics Drupal\DrupalExtension\Context\MinkContext::assertAtPath
      $this->getSession()->visit($this->locatePath($href));

      try {
        $this->getSession()->getStatusCode();
        $this->assertSession()->statusCodeEquals('200');

        // Check that something exists on the page and that its not a 200 white
        // screen or error page.
        $this->getSession()->getPage()->hasLink('Stanford Home');
        print "200 Success \n";
      }
      catch (UnsupportedDriverActionException $e) {
        // Simply continue on, as this driver doesn't support HTTP response codes.
      }
      catch (ExpectationException $e) {
        print "200 Success NOT received \n";
        throw new \Exception("Page at URL $href did not return 200 code.");
      }
      catch (DriverException $e) {
        throw new \Exception($e->getMessage());
      }
      print "\n";
    }

    print "Done! Checked $count Links";
  }

  /**
   * Check if the give href should be scanned.
   *
   * @param string $href
   *   Link href attribute.
   *
   * @return bool
   *   True if link needs to be checked.
   */
  protected function checkLink($href) {
    if (empty($href)) {
      return FALSE;
    }
    if (isset($this->visited_links[$href])) {
      return FALSE;
    }
    // Save URL for later to avoid duplicates.
    $this->visited_links[$href] = $href;
    // Skip if an anchor tag
    if (strpos($href, '#') === 0) {
      return FALSE;
    }
    // Skip remote links
    if (strpos($href, 'http') === 0) {
      return FALSE;
    }
    // Skip mailto links
    if (strpos($href, 'mailto') === 0) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Return a region from the current page.
   *
   * @throws \Exception
   *   If region cannot be found.
   *
   * @param string $region
   *   The machine name of the region to return.
   *
   * @return \Behat\Mink\Element\NodeElement
   *
   * @todo this should be a trait when PHP 5.3 support is dropped.
   */
  public function getRegion($region) {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }

    return $regionObj;
  }

}
