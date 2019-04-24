@install
Feature: Install State Courses
  In order to verify a new site is functional
  As an administrator
  I should have default content types, permissions, and content already created.

  @api @safe
  Scenario: Test basic page with postcard paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_course"
    And I fill in the following:
      | Title | Title |
      | Requirements | Requirements |
      | Course Code | Course Code |
      | Course Code Integer | 111 |
      | Course ID | 222 |
      | Grading | Grading |
      | Component | Component |
      | Subject | Subject |
      | Units | Units |
      | Course Tags | Course Tags |
      | Course Link | http://google.com |
      | Body | Body |
      | Section ID | 333 |
      | Section Number | 444 |
      | Location | Location |
      | Section Days | Section Days |
      | field_hs_course_section_st_date[0][value][date] | 2028-10-01 |
      | Start Time | 10:00 AM |
      | field_hs_course_section_end_date[0][value][date] | 2028-12-31 |
      | End Time | 11:00 AM   |
    And I select "2028 - 2029" from "Academic Year"
    And I select "Autumn" from "Quarter"
    And I select "Undergraduate" from "Academic Career"
    Then I press "Save"
    And I should be on "/courses/title/444"
    And the response status code should be 200
    Then I should be on "/courses/title/444"
    And I should see the heading "Title" in the "content" region
    And I should see "Requirements"
    Then I am logged in as a user with the "Authenticated" role
    And I am on "/courses/title/444"
    Then I should be on "http://google.com"
