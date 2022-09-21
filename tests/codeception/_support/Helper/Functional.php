<?php

namespace Helper;

use Codeception\Module;

/**
 * Class Functional.
 *
 * Here you can define custom actions.
 * All public methods declared in helper class will be available in $I.
 *
 * @link https://codeception.com/docs/06-ModulesAndHelpers
 */
class Functional extends Module {

  /**
 * @param int $timeout : timeout period
 * @throws ModuleException
 */
  public function waitPageLoad($timeout = 10) {
    $this->webDriverModule->waitForJs('return document.readyState == "complete"', $timeout);
  }
}
