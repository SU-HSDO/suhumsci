@install
Feature: Install State
  In order to verify a user has correct permission
  Permissions should be set for each role correctly.

  @api @safe
  Scenario: Test default permissions.
    Given I run drush "cget user.role.authenticated permissions"
    Then the role "authenticated" should have 13 permissions
    And drush output should contain "access content"
    And drush output should contain "access shortcuts"
    And drush output should contain "search content"
    And drush output should contain "use text format basic_html_without_media"
    And drush output should contain "view any course_collections entities"
    And drush output should contain "view any event_collections entities"
    And drush output should contain "view any publications_collections entities"
    And drush output should contain "view field_hs_hero_overlay_color"
    And drush output should contain "view field_hs_text_area_bg_color"
    And drush output should contain "view media"
    And drush output should contain "view own course_collections entities"
    And drush output should contain "view own field_hs_hero_overlay_color"
    And drush output should contain "view the administration theme"
