@global
Feature: Main Menu Links
  In order to verify the main menu is functional
  As a visitor
  I should get a valid response from all pages in the menu.

  @api @safe
  Scenario: Test for Footer Links.
    Given I am logged in as a user with the "Developer" role
    And I am on "/"
    Then the response status code should be 200
    And every link in the "header" region should work
