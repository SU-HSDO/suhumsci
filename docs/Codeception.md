# Codeception Tests

The following Codeception tests are currently run during a CI build. Unless otherwise specified, the test includes creation of a entity and verification that it appears correctly on the front-end after save.

## Acceptance

* [Course](../tests/codeception/acceptance/Install/Content/CourseCest.php)
* [Flexible Page](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php)
  * [Postcard](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php#L29)
  * [Accordion](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php#L54)
  * [Back to top block](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php#L71)
  * [Text area](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php#L92)
  * [Collections (2 items per row - Text Area and Postcard)](../tests/codeception/acceptance/Install/Content/FlexiblePageCest.php#L128)
* [Private Page Field Test](../tests/codeception/acceptance/Install/Content/PrivatePageContentCest.php)
  * With Site Manager role, test following field visibility:
    * Private Text Area
    * Private Collection
    * Spotlight - Slider
    * Accordion
    * Postcard
* [Private Page Permissions](../tests/codeception/acceptance/Install/Content/PrivatePagePermissionCest.php)
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
* [Permissions Testing - verify the following role permissions in config match permissions in database](../tests/codeception/acceptance/Install/Roles)
  * [Anonymous](../tests/codeception/acceptance/Install/Roles/AnonymousCest.php)
  * [Authenticated user](../tests/codeception/acceptance/Install/Roles/AuthenticatedCest.php)
  * [Contributor](../tests/codeception/acceptance/Install/Roles/ContributorCest.php)
  * [Site Manager](../tests/codeception/acceptance/Install/Roles/SiteManagerCest.php)
* [Install State](../tests/codeception/acceptance/Install/InstallStateCest.php)
  * [Default Content (Home page)](../tests/codeception/acceptance/Install/InstallStateCest.php#L29)
    * Text input
    * Search button
    * Specific content
  * [Visible Admin Items](../tests/codeception/acceptance/Install/InstallStateCest.php#L44)
    * Admin menu items
    * Specific people in user list
  * Specific number of shortcuts for specific roles
    * [Contributor](../tests/codeception/acceptance/Install/InstallStateCest.php#L62)
    * [Site Manager](../tests/codeception/acceptance/Install/InstallStateCest.php#L73)
    * [Developer](../tests/codeception/acceptance/Install/InstallStateCest.php#L84)
  * [Unpublished Menu Items](../tests/codeception/acceptance/Install/InstallStateCest.php#L93)
    * Site Managers should be able to place a page under an unpublished page in the menu
    * Tests adding menu items and verifying they exist after save
  * [Fast 404](../tests/codeception/acceptance/Install/InstallStateCest.php#L127)
    * Create a node and redirect to the node
    * Test visiting the redirect and verify redirect works to the node
* [Paragraphs](../tests/codeception/acceptance/Paragraphs/ParagraphsCest.php)
  * Tests specific paragraphs included/excluded from collections on:
    * Private Page
    * Public Collections
    * Flexible Page
* [Menu Items](../tests/codeception/acceptance/MenuItemsCest.php)
  * [Validity of menu links in header](../tests/codeception/acceptance/MenuItemsCest.php#L48)
  * [Pathauto automatic aliasing of paths](../tests/codeception/acceptance/MenuItemsCest.php#L64)

## Functional

* [Flexible Page](../tests/codeception/functional/Install/Content/FlexiblePageCest.php)
  * [Hero](../tests/codeception/functional/Install/Content/FlexiblePageCest.php#L36)
  * [Photo Album](../tests/codeception/functional/Install/Content/FlexiblePageCest.php#L74)
  * [Mobile Menu button and toggles](../tests/codeception/functional/Install/Content/FlexiblePageCest.php#L117)
  * [Spotlight Slider (with 2 slides)](../tests/codeception/functional/Install/Content/FlexiblePageCest.php#L173)
  * [Vertical Timeline](../tests/codeception/functional/Install/Content/FlexiblePageCest.php#L242)
* [Video Embed](../tests/codeception/functional/Install/Content/VideoEmbedCest.php)
  * Title
  * Text field
  * Media (YouTube video via URL)
  * Caption
* [Media](../tests/codeception/functional/MediaCest.php)
  * [Document (txt file)](../tests/codeception/functional/MediaCest.php#L14)
  * [Cannot upload PHP files](../tests/codeception/functional/MediaCest.php#L30)
  * [Images](../tests/codeception/functional/MediaCest.php#L41)
  * [Video (YouTube via URL)](../tests/codeception/functional/MediaCest.php#L59)
* [MegaMenu](../tests/codeception/functional/MegaMenuCest.php)
  * Enable MegaMenu
  * Add top level and second level item
  * Toggles menu items
  * Mobile menu button and toggles
