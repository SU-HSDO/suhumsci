@install
Feature: Install State
  In order to verify a new site is functional
  As an administrator
  I should have default content types, permissions, and content already created.

  @api @safe
  Scenario: Test default content.
    Given I am on "/"
    Then the response status code should be 200
    And I should see an "input[type='text']" element
    And I should see the button "Search"
    And I should see "Class aptent taciti sociosqu ad litora torquent per conubia nostra"
    And I should see the heading "About"
    And I should see the heading "People"
    And I should see the heading "Connect With Us"
    And I should see the heading "Contact Us"


  @api @safe
  Scenario: Test admin visible items
    Given I am logged in as a user with the "Developer" role
    Then I am on "/admin/content"
    And I should see the link "Content"
    And I should see the link "Files"
    And I should see the link "Media"
    And I should see the link "Add content"
    And I should see the link "Home Page"
    Then I am on "/admin/users"
    And I should see the link "Howard"
    And I should see the link "Lindsey"

  @api @safe
  Scenario: Test shortcut menu items for contributor role
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/"
    And I should see 25 "#toolbar-item-shortcuts-tray a" elements

  @api @safe
  Scenario: Test shortcut menu items for site manager role
    Given I am logged in as a user with the "Site Manager" role
    Then I am on "/"
    And I should see 30 "#toolbar-item-shortcuts-tray a" elements

  @api @safe
  Scenario: Test shortcut menu items for developer role
    Given I am logged in as a user with the "Developer" role
    Then I am on "/"
    And I should see 38 "#toolbar-item-shortcuts-tray a" elements
    Then I click the "#toolbar-item-shortcuts-tray a[href='/google-analytics']" element
    And I should be on "https://analytics.google.com/analytics/web/"
