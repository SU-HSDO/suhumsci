<?php

use Drupal\Core\Serialization\Yaml;

/**
 * Class AuthenticatedCest.
 *
 * @group install
 * @group roles
 */
class AuthenticatedCest {

  /**
   * Authenticated permissions should match the config.
   */
  public function testPermissions(AcceptanceTester $I) {
    $permissions = json_decode($I->runDrush('cget user.role.authenticated permissions --format=json'), TRUE);
    $config = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/default/user.role.authenticated.yml'));
    $I->assertEquals($permissions['user.role.authenticated:permissions'], $config['permissions']);
  }

}
