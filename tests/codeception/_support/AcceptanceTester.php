<?php

use Codeception\Actor;

/**
 * Define custom actions here.
 *
 * @link https://codeception.com/docs/02-GettingStarted#generators
 *
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor {

  use _generated\AcceptanceTesterActions;

  /**
   * Go to the front page.
   */
  public function amOnTheHomepage() {
    $this->amOnPage('/');
  }

}
