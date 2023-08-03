<?php

use Drupal\Core\Serialization\Yaml;

/**
 * Class ContributorCest.
 *
 * @group install
 * @group roles
 */
class ContributorCest {

  /**
   * Permissions should match the config.
   */
  public function testPermissions(AcceptanceTester $I) {
    $permissions = json_decode($I->runDrush('cget user.role.contributor permissions --format=json'), TRUE);
    $config = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/default/user.role.contributor.yml'));
    $I->assertEquals($permissions['user.role.contributor:permissions'], $config['permissions']);
  }

}
