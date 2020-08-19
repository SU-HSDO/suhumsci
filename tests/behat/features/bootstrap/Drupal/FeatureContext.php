<?php

namespace Drupal;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

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
   * Create an HTML file of the current page if a test failed.
   *
   * @AfterStep
   */
  public function afterStep(AfterStepScope $event) {
    if (!$event->getTestResult()->isPassed()) {
      $test_title = $event->getFeature()->getTitle();
      $test_title = preg_replace("/[^a-z]/", '_', strtolower($test_title));
      $line = $event->getStep()->getLine();
      $page = $this->getSession()->getPage();
      $drupal_directory = $this->getDrupalParameter('drupal')['drupal_root'];
      if (!file_exists("$drupal_directory/../artifacts/")) {
        mkdir("$drupal_directory/../artifacts/");
      }
      file_put_contents("$drupal_directory/../artifacts/$test_title-$line.html", $page->getOuterHtml());
    }
  }

  /**
   * @Then I create a screenshot
   */
  public function createScreenshot() {
    $page = $this->getSession()->getPage();
    file_put_contents(__DIR__ . '/test.html', $page->getOuterHtml());
  }

  /**
   * @Then :button should be disabled
   */
  public function iShouldNotBeAbleToPress($button) {
    $button = $this->getSession()->getPage()->findButton($button);
    Assert::assertTrue($button->hasAttribute('disabled'));
  }

  /**
   * @Then the role :role_id should have :count permissions
   */
  public function theRoleShouldHavePermissions($role_id, $count) {
    $permissions = [];
    $command = "rls --format=json";
    $drush_output = $this->getDriver('drush')->$command();
    $roles = json_decode($drush_output, TRUE);
    if (isset($roles[$role_id])) {
      $permissions = $roles[$role_id]['perms'];
    }
    Assert::assertCount((int) $count, $permissions);
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
   * @param string $region
   *   The machine name of the region to return.
   *
   * @return \Behat\Mink\Element\NodeElement
   *
   * @throws \Exception
   *   If region cannot be found.
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

  /**
   * Cleans up files after every scenario.
   *
   * @AfterScenario @MediaCleanup
   */
  public function cleanUpMedia($event) {
    $user = $this->getUserManager()->getCurrentUser();
    if (!$user) {
      return;
    }
    $media_entities = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->loadByProperties(['uid' => $user->uid]);

    foreach ($media_entities as $media_item) {
      $entity = new \stdClass();
      $entity->id = $media_item->id();
      $this->getDriver()->entityDelete('media', $entity);
    }

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uid' => $user->uid]);

    foreach ($files as $file) {
      $entity = new \stdClass();
      $entity->id = $file->id();
      $this->getDriver()->entityDelete('file', $entity);
      /** @var \Drupal\Core\File\FileSystemInterface $fs */
      $fs = \Drupal::service("file_system");echo $fs->realpath("private://");
    }
  }

}
