<?php

/**
 * Class MediaCest.
 *
 * @group install
 * @group existingSite
 */
class MediaCest {

  /**
   * Documents can be uploaded.
   */
  public function testDocuments(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add');
    $I->click('Bulk Upload');
    $I->dropFileInDropzone(__DIR__ . '/test.txt');
    $I->wait(1);
    $I->click('Upload');
    $I->fillField('Name', 'Demo Text File');
    $I->click('Save');
    $I->canSee('Saved 1 Media Items');
    $I->canSeeInCurrentUrl('/admin/content/media');
    $I->canSee('Demo Text File');
  }

  /**
   * A php file can't be uploaded.
   */
  public function testBadDocuments(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add');
    $I->click('Bulk Upload');
    $I->dropFileInDropzone(__FILE__);
    $I->canSeeElement('.dz-error.dz-complete');
  }

  /**
   * An image can be uploaded.
   */
  public function testImages(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add');
    $I->click('Bulk Upload');
    $I->dropFileInDropzone(__DIR__ . '/logo.jpg');
    $I->click('Upload');
    $I->fillField('Name', 'Logo File');
    $I->click('.claro-details summary');
    $I->uncheckOption('Decorative Image');
    $I->fillField('Alternative text', 'Stanford Logo');
    $I->click('Save');
    $I->canSee('Saved 1 Media Items');
    $I->canSeeInCurrentUrl('/admin/content/media');
    $I->canSee('Logo File');
  }

  /**
   * A youtube video can be added.
   */
  public function testVideo(FunctionalTester $I){
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add');
    $I->click('Video', '.region-content');
    $I->fillField('Name', 'Test Video');
    $I->fillField('Video URL', 'http://google.com');
    $I->click('Save');
    $I->canSee('1 error has been found');
    $I->fillField('Video URL', 'https://www.youtube.com/watch?v=-DYSucV1_9w');
    $I->click('Save');
    $I->canSee('Test Video has been created.');
  }
}
