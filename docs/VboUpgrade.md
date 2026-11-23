# VBO Upgrade Validation

## Changes

The upgrade uses the VBO release locked in [composer.lock](../composer.lock), with the minimum compatible release specified in [composer.json](../composer.json). Only the VBO package changes in the lock file; its production dependency requirements remain unchanged.

- [CloneNode](../docroot/modules/humsci/hs_actions/src/Plugin/Action/CloneNode.php) declares an `array` return type on `defaultConfiguration()` to match its upstream parent class.
- The select-all Composer patch and its local file are removed because [upstream includes the selection controls](https://www.drupal.org/project/views_bulk_operations/releases/4.4.0).
- The [clone action kernel test](../docroot/modules/humsci/hs_actions/tests/src/Kernel/Plugin/Action/CloneNodeTest.php) covers VBO's multiple-entity execution with two selected nodes and multiple clones per node.

Later upstream releases also fix selection tempstore initialization, AJAX selection controls, Gin compatibility, confirmation queries and action inheritance. See the [target release notes](https://www.drupal.org/project/views_bulk_operations/releases/4.4.9) and [release history](https://www.drupal.org/project/views_bulk_operations/releases).

## Migration Assessment

The [missing database update was restored upstream](https://www.drupal.org/project/views_bulk_operations/issues/3553049). The locked release contains `views_bulk_operations_update_8036()`, so no restoration patch is required.

The platform's former select-all patch already included that same update number. Read-only remote Drush checks confirmed that all configured sites have recorded schema version `8036` and use valid values for `show_select_all` and `show_multipage_selection_box`:

| Environment | Sites Checked | Schema Version | VBO Fields Checked | Invalid Selection Values | Errors or Unchecked Sites |
|---|---|---|---|---|---|
| Development | 132 | 8036 | 4,349 | 0 | 0 |
| Staging | 132 | 8036 | 4,349 | 0 | 0 |
| Production | 132 | 8036 | 4,349 | 0 | 0 |

These are 396 site/environment checks. The preflight reads the schema key and all active View definitions, including site-specific and disabled Views and every display. It makes no configuration or entity changes. Raw outputs and summaries were retained outside version control.

The upstream update still has [known value-conversion errors](https://www.drupal.org/project/views_bulk_operations/issues/3557660), including `show`/`hide` values that differ from the current `always_show`/`always_hide` options. Its fallback can also overwrite existing selection settings. The preflight found no invalid values, and the update will be skipped on the audited sites because it has already run. No additional migration patch is included for this upgrade.

> **Important:** Do not reset the recorded schema version or manually rerun update `8036`. Preserve existing selection settings, especially `show_select_all: always_hide`. If a site has a lower schema version, review its migration separately before upgrading it.

Legacy `force_selection_info` keys remain in shared exported Views and active configuration. The previous patch already replaced that option with the two current options. Do not rerun the migration to remove those keys. Any later cleanup must preserve the current selection settings.

## Local Validation

The complete platform PHPUnit suite passes in DDEV (configured PHP and MySQL containers): 135 tests and 629 assertions. This includes the existing field-clone tests and the new multiple-entity clone regression. Full-repo PHPCS (`2,321` files), full-repo PHPStan (`[OK] No errors`), and Composer validation pass with zero lockfile errors (only pre-existing unrelated constraint warnings).

Local upgrade checks on the DDEV default site confirmed that update `8036` was already recorded (`No pending updates`), `drush ci -y && drush ci -y` and `drush cr` completed cleanly, and targeted Codeception validation (`InstallStateCest:testVisibleAdminItems`, verifying `/admin/content` and `/admin/users`) passed (`1` test, `9` assertions).

Earlier validation against a disposable local build with the prior patched release also verified upstream VBO unit and kernel tests (12 tests, 180 assertions), config/action preservation across 26 local VBO fields, and HTTP checks across Content, Manage Content, Duplicate Events and Files.

Additional upstream functional and JavaScript tests run against disposable local Drupal installations with headless Chrome. The initial run passes 13 of 14 tests. The unmodified dynamic-insertion test fails first with a stale-element error and then with a missing status message on a focused retry. A temporary copy that waits for checkbox AJAX and the resulting status message passes, including checks that exactly the three originally selected entities are processed after new nodes appear. This supports a timing issue in the local test harness; no contrib test files are changed. The full platform Codeception suites and staging UAT have not run against the upgraded code.

## Staging Validation

Use a preview or an agreed staging deployment for the remaining platform checks. The read-only fleet preflight does not validate the upgraded code on staging.

1. Confirm the installed VBO schema version before deployment:

   ```bash
   vendor/bin/drush @<SITENAME>.stage php:eval 'echo \Drupal::keyValue("system.schema")->get("views_bulk_operations"), PHP_EOL;'

   # Example:
   vendor/bin/drush @anthropology.stage php:eval 'echo \Drupal::keyValue("system.schema")->get("views_bulk_operations"), PHP_EOL;'
   ```

1. Install dependencies from the lock file and run the normal deployment updates. Verify that VBO does not queue update `8036` on a site where it is already recorded as complete.
1. Import configuration twice using `drush ci -y && drush ci -y`, then run `drush cr`. Follow [Configuration Management](Config.md); do not use a partial-import flag.
1. Run the platform [PHPUnit and Codeception suites](Testing.md) in the installed environment.
1. Test the Content, Files, Manage Content and Duplicate Events Views as Site Manager and Contributor. Confirm that hidden select-all controls stay hidden and that selected rows remain correct across pagination and exposed-filter changes.
1. Test cloning, clone counts, nested paragraphs and date adjustments; bulk field editing; migration-ignore permissions and behavior; and deletion confirmation. Include site-specific Views on Faculty Affairs and Linguistics.
1. Check AJAX selection, action selection before selecting rows, browser back navigation and two tabs with the same View. Confirm that execution affects exactly the intended entities, including when new content appears between selection and execution.
1. Where a View has another form handler, verify that submitting that handler does not execute the VBO action.
1. Review results with the H&S web team before scheduling production deployment.
