# Codeception Tests

The following Codeception tests are currently run during a CI build. Unless otherwise specified, the test includes creation of a entity and verification that it appears correctly on the front-end after save.

## Acceptance

* Course
* Flexible Page
  * Postcard
  * Accordion
  * Back to top block
  * Text area
  * Collections (2 items per row - Text Area and Postcard)
* Private Page Field Test
  * With Site Manager role, test following field visibility:
    * Private Text Area
    * Private Collection
    * Spotlight - Slider
    * Accordion
    * Postcard
* Private Page Permissions
  * Test access to private page for role:
    * Site Manager
    * Developer
    * Contributor
    * Intranet Viewer
  * Test access denied to private page for role:
    * Author
    * Stanford Faculty
    * Stanford Staff
    * Stanford Student
    * Authenticated user
    * Annoymous user
* Permissions Testing - verify the following role permssions in config match permissions in database
  * Anonymous
  * Authenticated user
  * Contributor
  * Site Manager
* Install State
  * Default Content (Home page)
    * Text input
    * Search button
    * Specific content
  * Visible Admin Items
    * Admin menu items
    * Specific people in user list
  * Specific number of shortcuts for specific roles
    * Contributor
    * Site Manager
  * Unpublished Menu Items
    * Site Managers should be able to place a page under an unpublished page in the menu
    * Tests adding menu items and verifying they exist after save
  * Fast 404
    * Create a node and redirect to the node
    * Test visiting the redirect and verify redirect works to the node
* Paragraphs
  * Tests specific paragraphs included/excluded from collections on:
    * Private Page
    * Rows
    * Public Collections
    * Flexible Page
* Menu Items
  * Validity of menu links in header
  * Pathauto automatic aliasing of paths

## Functional

* Flexible Page
  * Hero
  * Photo Album
  * Mobile Menu button and toggles
  * Spotlight Slider (with 2 slides)
  * Vertical Timeline
* Video Embed
  * Title
  * Text field
  * Media (YouTube video via URL)
  * Caption
* Media
  * Document (txt file)
  * Cannot upload PHP files
  * Images
  * Video (YouTube via URL)
* MegaMenu
  * Enable MegaMenu
  * Add top level and second level item
  * Toggles menu items
  * Mobile menu button and toggles

### Disabled Acceptance Tests
  * Flexible Page - Row with Text Area
