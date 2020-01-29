<?php

namespace Drupal;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Javascript related tests.
 */
class FeatureMinkContext extends RawMinkContext {

  protected $files = [];

  /**
   * @Then I maximize the window
   */
  public function maximizeWindow() {
    $this->getSession()->getDriver()->maximizeWindow();
  }

  /**
   * @Then I set window dimensions :width x :height
   */
  public function iSetWindowDimensions($width, $height) {
    $this->getSession()->resizeWindow((int) $width, (int) $height, 'current');
  }


}
