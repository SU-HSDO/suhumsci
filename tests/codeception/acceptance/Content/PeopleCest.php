<?php

use Faker\Factory;

/**
 * People content type test.
 */
class PeopleCest {

  /**
   * The node should generate the title from the first middle and last names.
   *
   * @group install
   * @group existingSite
   */
  public function testPeopleNames(AcceptanceTester $I) {
    $faker = Factory::create();
    $first_name = $faker->firstName;
    $middle_name = $faker->firstName;
    $last_name = $faker->lastName;
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_person');
    $I->fillField('First Name', $first_name);
    $I->fillField('Middle Name', $middle_name);
    $I->fillField('Last Name', $last_name);
    $I->click('Save');
    $I->canSee("$first_name $middle_name $last_name", 'h1');
  }

}
