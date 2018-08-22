@archaeology
Feature: Archaeology
  In order to verify Archaeology site is functional
  As a user
  I should validate some content

#  @javascript
#  Scenario: Test the Javascript works.
#    Given I am on "/"
#    Then I should be on "/"


  Scenario: Test for Footer Links.
    Given I am on "/"
    Then the response status code should be 200
    And I should see the link "Archaeology Center" in the "header" region
