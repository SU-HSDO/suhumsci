<?php

namespace Helper;

use Codeception\Module;

/**
 * Class Acceptance.
 *
 * Here you can define custom actions.
 * All public methods declared in helper class will be available in $I.
 *
 * @link https://codeception.com/docs/06-ModulesAndHelpers
 */
class Acceptance extends Module {

  /**
   * The current request should not contain a specific header in the response.
   *
   * @param string $header
   *   Header key.
   */
  public function cantSeeResponseHeader($header) {
    /** @var \Codeception\Module\PhpBrowser $browser */
    $browser = $this->getModule('PhpBrowser');
    /** @var \Symfony\Component\BrowserKit\Response $response */
    $response = $browser->client->getResponse();
    $headers = $response->getHeaders();
    $this->assertArrayNotHasKey($header, $headers, sprintf('Header "%s" exists in the current response', $header));
  }

  function seePageHasElement($element) {
    try {
      $this->getModule('PhpBrowser')->_findElements($element);
      //echo "in try block..." . $this->getModule('PhpBrowser')->_findElements($element);
      //print_r($this->getModule('PhpBrowser')->_findElements($element));
      return true;
    } catch (\PHPUnit_Framework_AssertionFailedError $f) {
      echo "in catch block...";
      return false;
    }
    //return true;
  }
}
