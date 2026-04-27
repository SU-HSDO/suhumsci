<?php

use Faker\Factory;

/**
 * Class TrainingCest.
 *
 * @group install
 */
class TrainingCest {

  /**
   * Faker service.
   *
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * Test constructor.
   */
  public function __construct() {
    $this->faker = Factory::create();
  }

  /**
   * Training nodes with a date get a URL under /trainings/.
   */
  public function testTrainingWithDate(AcceptanceTester $I) {
    $term = $I->createEntity([
      'name' => $this->faker->words(3, TRUE),
      'vid' => 'hs_training_name',
    ], 'taxonomy_term');

    $node = $I->createEntity([
      'type' => 'hs_training',
      'field_hs_training_name' => [['target_id' => $term->id()]],
      'field_hs_training_date_range' => [[
        'value' => strtotime('2027-06-15 09:00:00'),
        'end_value' => strtotime('2027-06-15 10:00:00'),
      ]],
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSeeResponseCodeIs(200);
    $I->canSeeInCurrentUrl('/trainings/');
  }

  /**
   * Training nodes with no scheduled date can be created and are accessible.
   */
  public function testTrainingNoDate(AcceptanceTester $I) {
    $term = $I->createEntity([
      'name' => $this->faker->words(3, TRUE),
      'vid' => 'hs_training_name',
    ], 'taxonomy_term');

    $node = $I->createEntity([
      'type' => 'hs_training',
      'field_hs_training_name' => [['target_id' => $term->id()]],
      'field_hs_training_nodate' => TRUE,
    ]);

    $I->amOnPage($node->toUrl()->toString());
    $I->canSeeResponseCodeIs(200);
    $I->canSeeInCurrentUrl('/trainings/');
  }

}
