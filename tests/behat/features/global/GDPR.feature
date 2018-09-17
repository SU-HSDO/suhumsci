@global
Feature: GDPR Links
  In order to verify that we are compliant with GDPR
  As a visitor
  I should see a variety of links.

#  @javascript
#  Scenario: Test the Javascript works.
#    Given I am on "/"
#    Then I should be on "/"

  @api @safe
  Scenario: Test for Footer Links.
    Given I am logged in as a user with the "Developer" role
    And I am on "/"
    Then the response status code should be 200
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
