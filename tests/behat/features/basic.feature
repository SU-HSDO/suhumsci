@example
Feature: Web drivers
  In order to verify that web drivers are working
  As a user
  I should be able to load the homepage
  With and without Javascript

#  @javascript
#  Scenario: Test the Javascript works.
#    Given I am on "/"
#    Then I should be on "/"


  Scenario: Test for Footer Links.
    Given I am on "/"
    Then the response status code should be 200
    And I should see "Search" in the "search" region
    And I should see the link "Stanford Home" in the "global_footer" region
    And I should see the link "Maps & Directions" in the "global_footer" region
    And I should see the link "Search Stanford" in the "global_footer" region
    And I should see the link "Emergency Info" in the "global_footer" region
    And I should see the link "Terms of Use" in the "global_footer" region
    And I should see the link "Privacy" in the "global_footer" region
    And I should see the link "Copyright" in the "global_footer" region
    And I should see the link "Trademarks" in the "global_footer" region
    And I should see the link "Non-Discrimination" in the "global_footer" region
    And I should see the link "Accessibility" in the "global_footer" region
