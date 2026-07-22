# Testing

This project uses two test suites: PHPUnit for unit and kernel tests, and Codeception for acceptance and functional tests. Both suites run on every pull request in GitHub Actions and must pass before the pull request can be merged.

## PHPUnit

Custom module and profile tests live in `docroot/modules/humsci` and `docroot/profiles/humsci`, and run under a single testsuite named `stanford`. Only Unit and Kernel tests are run; Functional and FunctionalJavascript tests aren't used on this project, since Codeception covers acceptance and functional testing instead.

Run the suite with:

```bash
drush sws:source:tests:phpunit
```

The `--with-coverage` flag exists but isn't used in CI or configured on this project; see [Coverage](#coverage) below.

### Configuration

`tests/phpunit/example.phpunit.xml` is the source configuration file. Running the test command copies it to `docroot/core/phpunit.xml`, substituting local database and environment values, and runs PHPUnit against the generated file. Edit `tests/phpunit/example.phpunit.xml`, not the generated `docroot/core/phpunit.xml` file directly, since it gets overwritten on the next run.

> **Important:** Drupal core's own `docroot/core/phpunit.xml.dist` is the reference for what a current, correctly configured PHPUnit setup looks like. When core's PHPUnit version or configuration format changes between upgrades, diff `example.phpunit.xml` against the current `phpunit.xml.dist` and update it to match. See `docroot/core/tests/README.md` for a full explanation of available settings.

> **Tip:** A kernel test can fail after a core upgrade if a plugin it exercises extends a class from a module the test doesn't declare in its `$modules` array. Plugin discovery walks the full parent class chain, so every module in that chain needs to be enabled in the test, not just the module that defines the plugin under test.

### Coverage

Coverage is not currently configured or used on this project. CI runs the suite without `--with-coverage`, and there's no `<source><include>` block in `example.phpunit.xml` and no enforced minimum threshold.

If coverage is reintroduced in the future: coverage instrumentation only tracks whatever paths are listed under `<source><include>` in the PHPUnit configuration. If that list doesn't point at the actual custom code location (`docroot/modules/humsci`), the reported percentage doesn't mean anything, since it's only measuring lines that happen to execute in files nothing is instrumented against. Before relying on a coverage number or gating on it with `sws:tests:phpunit-coverage-check`, confirm `<source><include>` actually covers the code you care about measuring.

## Codeception

Acceptance and functional testing is done using the [Codeception](https://codeception.com/) framework.

```bash
drush sws:codeception
```

Run tests annotated with a specific group with `drush sws:codeception --group=<GROUP_NAME>` (for example, `drush sws:codeception --group=roles`). This is the most effective way to run a single test.

The following Codeception tests currently run in CI. Unless otherwise noted, a test creates an entity and verifies it appears correctly on the front end after save.

### Acceptance

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
    * Anonymous user
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

### Functional

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
