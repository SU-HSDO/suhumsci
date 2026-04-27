<?php

/**
 * Class TrainingCest.
 *
 * @group install
 */
class TrainingCest {

  /**
   * Resize the window at the start of each test.
   */
  public function _before(FunctionalTester $I) {
    $I->resizeWindow(2000, 1400);
  }

  /**
   * The date range field hides when "no date" is checked and reappears on uncheck.
   */
  public function testNoDateConditionalField(FunctionalTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_training');

    $I->waitForElement('.field--name-field-hs-training-date-range');
    $I->seeElement('.field--name-field-hs-training-date-range');

    $I->checkOption('This training does not have a date');
    $I->waitForElementNotVisible('.field--name-field-hs-training-date-range');

    $I->uncheckOption('This training does not have a date');
    $I->waitForElementVisible('.field--name-field-hs-training-date-range');
  }

}
