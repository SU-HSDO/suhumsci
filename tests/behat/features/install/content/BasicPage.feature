@install
Feature: Install State Basic Page
  In order to verify a new site is functional
  As an administrator
  I should have default content types, permissions, and content already created.

  @api @safe @javascript @MediaCleanup
  Scenario: Test basic page with Hero paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I set window dimensions 1200 x 3000
    Then I fill in "Title" with "Demo Basic Page"
    And I press "field_hs_page_hero_hs_hero_image_add_more"
    And I wait for AJAX to finish
    Then I should see "Hero Image"
    And I should see "Overlay Details"
    And I should not see "Optionally add some overlay text on top of the image"
    And I should not see "Body"
    And I should not see "Link text"
    And I should not see "Overlay Color"
    And I press "Add media"
    Then I wait for AJAX to finish
    Then I drop "images/logo.jpg" file into dropzone
    And I press "Upload and Continue"
    Then I wait for AJAX to finish
    And I wait 2 seconds
    And I click the ".media-library-widget-modal .ui-dialog-buttonpane .button" element
    And I wait for AJAX to finish
    And I wait 2 seconds
    Then I click the ".ui-dialog-buttonpane .media-library-select.button" element
    And I wait for AJAX to finish
    Then I click the "summary:contains(Overlay Details)" element
    And I should see "Optionally add some overlay text on top of the image"
    And I should see "Body"
    And I should see "Link text"
    And I should see "Overlay Color"
    And I fill in "field_hs_page_hero[0][subform][field_hs_hero_title][0][value]" with "Overlay Title"
    And I fill in wysiwyg "Body" with "Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem."
    And I fill in "URL" with "http://google.com"
    And I fill in "Link text" with "Google CTA"
    And I press "#4D4F53"
    And I press "Save"
    Then I should see 1 "img" elements in the "content" region
    And I should see "Overlay Title"
    And I should see "Vivamus in erat ut urna cursus vestibulum"
    And I should see "Google CTA"

  @api @safe
  Scenario: Test basic page with postcard paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I should see 1 "#edit-field-hs-page-hero-wrapper" elements
    And I should see 10 "#edit-field-hs-page-components-add-more input" elements
    Then I fill in "Title" with "Demo Basic Page"
    And I press "Add Postcard"
    And I should see "Card Title"
    And I should see "Card Body"
    And I should see "Read More Link"
    Then I fill in the following:
      | Card Title | Nam at tortor in tellus          |
      | Card Body  | Maecenas vestibulum mollis diam. |
      | URL        | Nam at tortor                    |
      | Link text  | Praesent egestas tristique nibh  |
    And I press "Save"
    Then I should see the error message "1 error has been found: URL"
    Then I fill in "URL" with "http://google.com"
    And I press "Save"
    Then I should be on "/demo-basic-page"
    And the response status code should be 200
    And I should see the heading "Nam at tortor in tellus" in the "content" region
    And I should see "Maecenas vestibulum mollis diam."
    And I should see the link "Praesent egestas tristique nib"

  @api @safe
  Scenario: Test basic page with Row paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I fill in "Title" with "Demo Basic Page"
    And I press "Add Row"
    And I should see 8 "#edit-field-hs-page-components-3-subform-field-hs-row-components-add-more-operations input" elements
    And I should see 3 "#edit-field-hs-page-components-3-subform-field-paragraph-style options" elements
    And I press "Add Accordion"
    Then I fill in "Summary" with "Sed augue ipsum egestas nec"
    And I fill in "Description" with "Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem."
    Then I press "Save"
    And I should be on "/demo-basic-page"
    And I should see the heading "Demo Basic Page" in the "content" region
    And I should see "Sed augue ipsum egestas nec"
    And I should see "Vivamus in erat ut urna cursus vestibulum"


  @api @safe
  Scenario: Test basic page with Accordion paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I fill in "Title" with "Demo Basic Page"
    And I press "Add Accordion"
    Then I fill in "Summary" with "Sed augue ipsum egestas nec"
    And I fill in "Description" with "Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem."
    Then I press "Save"
    And I should be on "/demo-basic-page"
    And I should see the heading "Demo Basic Page" in the "content" region
    And I should see "Sed augue ipsum egestas nec"
    And I should see "Vivamus in erat ut urna cursus vestibulum"

  @api @safe @javascript @MediaCleanup
  Scenario: Test basic page with Banner paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I set window dimensions 1200 x 3000
    Then I fill in "Title" with "Demo Basic Page"
    And I press "field_hs_page_hero_hs_banner_add_more"
    And I wait for AJAX to finish
    Then I should see "Banner Image"
    And I press "Add media"
    Then I wait for AJAX to finish
    Then I drop "images/logo.jpg" file into dropzone
    And I press "Upload and Continue"
    Then I wait for AJAX to finish
    And I wait 2 seconds
    And I click the ".media-library-widget-modal .ui-dialog-buttonpane .button" element
    And I wait for AJAX to finish
    And I wait 2 seconds
    Then I click the ".ui-dialog-buttonpane .media-library-select.button" element
    And I wait for AJAX to finish
    And I press "Save"
    And I should be on "/demo-basic-page"
    And I should see the heading "Demo Basic Page" in the "content" region
    And I should see 1 "img" elements in the "content" region

  @api @safe @javascript @MediaCleanup @testme
  Scenario: Test basic page with Text Area paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I set window dimensions 1200 x 3000
    And I fill in "Title" with "Demo Basic Page"
#    And I press "List additional actions"
#    And I wait 30 seconds
#    And I press "Add Text Area"
#    And I wait for AJAX to finish
    And I fill in wysiwyg "Text Area" with "Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem."
    Then I press "Save"
    And I should be on "/demo-basic-page"
    And I should see the heading "Demo Basic Page" in the "content" region
    And I should see "Vivamus in erat ut urna cursus vestibulum"
    And I should see 0 "img" elements in the "content" region
    Then I click "Edit"
    And I wait 30 seconds
    And I press "Edit" in the "content" region
    And I wait for AJAX to finish
    Then the "Text Area" field should contain "<p>Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem.</p>"
    Then I click the "a[title='Insert from Media Library']" element
    And I wait for AJAX to finish
    Then I drop "images/logo.jpg" file into dropzone
    And I press "Upload and Continue"
    Then I wait for AJAX to finish
    And I click the ".media-library-widget-modal .ui-dialog-buttonpane .button" element
    Then I wait for AJAX to finish
    Then I click the ".ui-dialog-buttonpane .media-library-select.button" element
    Then I wait for AJAX to finish
    Then I press "Save"
    And I should see 1 "img" elements in the "content" region

  @api @safe
  Scenario: Test basic page with Text Area paragraph creation
    Given I am logged in as a user with the "Contributor" role
    Then I am on "/node/add/hs_basic_page"
    And I fill in "Title" with "Demo Basic Page"
    And I press "Add Text Area"
    And I fill in "Text Area" with "Vivamus in erat ut urna cursus vestibulum. Sed augue ipsum, egestas nec, vestibulum et, malesuada adipiscing, dui. Curabitur suscipit suscipit tellus. Suspendisse enim turpis, dictum sed, iaculis a, condimentum nec, nisi. Nullam vel sem."
    Then I press "Save"
    And I should be on "/demo-basic-page"
    And I should see the heading "Demo Basic Page" in the "content" region
    And I should see "Vivamus in erat ut urna cursus vestibulum"
