@global
Feature: Main Menu Links
  In order to verify the main menu is functional
  As a visitor
  I should get a valid response from all pages in the menu.

  @safe
  Scenario: Test for Footer Links.
    Given I am on "/"
    Then the response status code should be 200
    And every link in the "menu" region should work
