<?php

class AuthenticatedCest {


  public function testPermissions(AcceptanceTester $I) {
    $permissions = json_decode($I->runDrush('cget user.role.authenticated permissions --format=json'), TRUE);
    $perms = [
      'access content',
      'access shortcuts',
      'search content',
      'use text format basic_html_without_media',
      'view any course_collections entities',
      'view any event_collections entities',
      'view any publications_collections entities',
      'view field_hs_accordion_views',
      'view field_hs_hero_overlay_color',
      'view field_hs_text_area_bg_color',
      'view field_media_embeddable_code',
      'view field_paragraph_style',
      'view media',
      'view own course_collections entities',
      'view own field_hs_accordion_views',
      'view own field_hs_hero_overlay_color',
      'view own field_media_embeddable_code',
      'view the administration theme',
    ];
    $I->assertCount(count($perms), $permissions['user.role.authenticated:permissions']);
    foreach ($perms as $perm) {
      $I->assertTrue(in_array($perm, $permissions['user.role.authenticated:permissions']));
    }
  }

}
