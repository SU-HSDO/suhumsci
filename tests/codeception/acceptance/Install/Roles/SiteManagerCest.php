<?php

use Drupal\Core\Serialization\Yaml;

/**
 * Class SiteManagerCest.
 *
 * @group install
 * @group roles
 */
class SiteManagerCest {

  /**
   * Permissions should match the configs.
   */
  public function testPermissions(AcceptanceTester $I) {
    $permissions = json_decode($I->runDrush('cget user.role.site_manager permissions --format=json'), TRUE);
    $config = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/../config/default/user.role.site_manager.yml'));
    $I->assertEquals($permissions['user.role.site_manager:permissions'], $config['permissions']);
  }

}
