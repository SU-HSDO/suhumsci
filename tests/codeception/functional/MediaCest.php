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
    $I->amOnPage('/media/add/file');
    $I->click('input[type="file"]');
    $I->dropFileInDropzone(__DIR__ . '/test.txt');
    $I->click('input[value="Upload"]');
    $I->fillField('Name', 'Demo Text File');
    $I->click('Save');
    $I->canSee('File Demo Text File has been created.');
    $I->canSeeInCurrentUrl('/admin/content/media');
    $I->canSee('Demo Text File');
  }

  /**
   * A php file can't be uploaded.
   */
  public function testBadDocuments(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add/file');
    $I->click('input[type="file"]');
    $I->dropFileInDropzone(__FILE__);
    $I->canSeeElement('.messages--error.file-upload-js-error');
  }

  /**
   * An image can be uploaded.
   */
  public function testImages(FunctionalTester $I) {
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add/image');
    $I->click('input[type="file"]');
    $I->dropFileInDropzone(__DIR__ . '/logo.jpg');
    $I->click('input[value="Upload"]');
    $I->fillField('Name', 'Logo File');
    $I->uncheckOption('Decorative Image');
    $I->fillField('Alternative text', 'Stanford Logo');
    $I->click('Save');
    $I->canSee('File Logo File has been created. ');
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
    $I->fillField('Video URL', 'https://google.com');
    $I->click('Save');
    $I->canSee('1 error has been found');
    $I->fillField('Video URL', 'https://www.youtube.com/watch?v=-DYSucV1_9w');
    $I->click('Save');
    $I->canSee('Test Video has been created.');
  }

}
