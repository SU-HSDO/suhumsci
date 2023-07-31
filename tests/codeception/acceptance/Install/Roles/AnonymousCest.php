<?php

use Drupal\Core\Serialization\Yaml;

/**
 * Class AnonymousCest.
 *
 * @group install
 * @group roles
 */
class AnonymousCest {

  /**
   * Anonymous user should have some permissions.
   */
  public function testPermissions(AcceptanceTester $I) {
    $permissions = json_decode($I->runDrush('cget user.role.anonymous permissions --format=json'), TRUE);
    $config = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/default/user.role.anonymous.yml'));
    $I->assertEquals($permissions['user.role.anonymous:permissions'], $config['permissions']);
  }

}
