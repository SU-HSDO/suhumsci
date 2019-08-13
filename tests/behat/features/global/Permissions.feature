@global
Feature: Permissions
  In order to Ensure custom permissions are kept
  As a user
  I should be able save permissions without removing other permissions.

  @api @safe
  Scenario: Test permission form submit
    Given I run drush "role-add-perm site_manager 'administer nodes'"
    Then I am logged in as a user with the "Developer" role
    And I am on "/admin/users/permissions"
    Then I press "Save permissions"
    Then I run drush "cget user.role.site_manager permissions"
    And drush output should contain "administer nodes"
