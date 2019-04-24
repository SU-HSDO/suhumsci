@global
Feature: Media
  In order to Media content creation
  As a user
  I should be able to create and edit media entities

  @api @safe @javascript @MediaCleanup
  Scenario: Test for valid documents.
    Given I am logged in as a user with the "Developer" role
    Then I maximize the window
    Then I am on "/media/add"
    And I should see "Audio" in the "content" region
    And I should see "Video" in the "content" region
    And I should see "Upload File(s)" in the content region
    Then I click "Upload File(s)"
    And I should be on "/admin/content/media/add/bulk"
    And I should see the link "Select files" in the "content" region
    And I should see the button "Upload"
    Then I drop "documents/test.txt" file into dropzone
    And I wait 1 seconds
    And I should see "test.txt"
    Then I press "Upload" in the "content" region
    And the "Name" field should contain "test.txt"
    Then I fill in "Name" with "Demo Text File"
    And I press "Save"
    And I should see the success message "Saved 1 Media Items"
    And I should be on "admin/content/media"
    And I should see "Demo Text File"

  @api @safe @javascript @MediaCleanup
  Scenario: Test for invalid documents.
    Given I am logged in as a user with the "Developer" role
    Then I maximize the window
    And I am on "/admin/content/media/add/bulk"
    Then I drop "documents/test.php" file into dropzone
    And I wait 1 seconds
    And I should see an ".dz-error.dz-complete" element
    And "Upload" should be disabled
    Then I click the ".dropzonejs-remove-icon" element
    And I should see 0 ".dz-preview" elements

  @api @safe @javascript @MediaCleanup
  Scenario: Test for valid Images.
    Given I am logged in as a user with the "Developer" role
    Then I maximize the window
    Then I am on "/admin/content/media/add/bulk"
    Then I drop "images/logo.jpg" file into dropzone
    Then I press "Upload" in the "content" region
    And the "Name" field should contain "logo.jpg"
    Then I fill in "Name" with "Demo Image File"
    And I fill in "Alternative text" with "Stanford Logo"
    And I fill in wysiwyg "Caption/Credit" with "Duis vel nibh at velit"
    And I press "Save"
    And I should see the success message "Saved 1 Media Items"
    And I should be on "admin/content/media"
    And I should see "Demo Image File"
    Then I am on "/node/add/hs_basic_page"
    And I fill in "Title" with "Fusce fermentum odio"
    And I press "Add Hero Image"
    Then I wait for AJAX to finish
    And I press "Continue"
    And I wait for AJAX to finish
    Then I switch to "entity_browser_iframe_image_browser" iframe
    And I click the "td.views-field-rendered-entity" element
    And I wait for AJAX to finish
    Then I press "Continue"
    And I wait for AJAX to finish
    And I exit iframe
    Then I press "Save"
    And I should see 1 "picture" elements in the "content" region
    And the element ".media picture img" should have the attribute "alt" with the value "Stanford Logo"

  @api @safe @javascript @MediaCleanup
  Scenario: Test for Audio creation.
    Given I am logged in as a user with the "Developer" role
    Then I maximize the window
    Then I am on "/media/add"
    And I click "Audio" in the "content" region
    Then I fill in "Name" with "Donec vitae sapien ut"
    And I fill in "Audio Url" with "http://google.com"
    Then I press "Save"
    And I should see the error message "1 error has been found: Audio Url"
    And I should see "Could not find an audio provider to handle the given URL"
    Then I fill in "Audio Url" with "https://soundcloud.com/lee-rowlands-1/drupal8-wont-kill-your-kittens"
    And I press "Save"
    Then I should be on "/admin/content/media"
    And I should see the message "Audio Donec vitae sapien ut has been created."

  @api @safe @javascript @MediaCleanup
  Scenario: Test for Video creation.
    Given I am logged in as a user with the "Developer" role
    Then I maximize the window
    Then I am on "/media/add"
    And I click "Video" in the "content" region
    Then I fill in "Name" with "Aenean commodo ligula eget dolor"
    And I fill in "Video Url" with "http://google.com"
    Then I press "Save"
    And I should see the error message "1 error has been found: Video Url"
    And I should see "Could not find a video provider to handle the given URL"
    Then I fill in "Video Url" with "https://youtu.be/-DYSucV1_9w"
    And I press "Save"
    Then I should be on "/admin/content/media"
    And I should see the message "Video Aenean commodo ligula eget dolor has been created."
