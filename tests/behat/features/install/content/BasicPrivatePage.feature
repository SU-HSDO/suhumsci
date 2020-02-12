@install @api
Feature: Basic Private Page
  In order to verify a new site is functional
  As an administrator
  I should have default content types, permissions, and content already created.

  @safe
  Scenario: Test basic page with Hero paragraph creation
    Given I am logged in as a user with the "Site Manager" role
    Then I am on "/node/add/hs_private_page"
    Then I fill in "Title" with "Test Private Page"
    And I press "Save"
    Then I should be on "/test-private-page"
    Then I am an anonymous user
    And I am on "/test-private-page"
    And the response status code should be 403
