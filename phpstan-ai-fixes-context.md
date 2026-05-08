# PHPStan AI Fixes Context

## Level 1

- Source report: `phpstan.log`
- Batch grouping:
  - Real code issues: missing returns for entity form `save()` methods and test doubles implementing Drupal interfaces.
  - Analyzer/environment issue: `GroupBlock` referenced `RefinableDependentAccess*` in the old `Drupal\block_content\Access` namespace; in this core version the symbols live in `Drupal\Core\Access`.
- Policy applied:
  - Code defects were fixed.
  - Legitimate analyzer/config findings may be documented without code or config changes.
- Follow-up note:
  - The `missingType.iterableValue` ignore in `phpstan.neon` did not match any current level 1 error. Per policy, no config change was made; keep under review for later levels.

## Level 2

- Batch 1 scope:
  - `hs_capx` form and unit test issues.
  - `hs_entities` form entity typing issues.
- Local hypothesis:
  - Most `method.notFound` findings here are caused by framework properties being treated as `EntityInterface` instead of the concrete config entity types used by the form handlers.
  - The `hs_capx` unit test still uses legacy PHPUnit mock annotations and has one outdated call signature.

- Batch 2 scope:
  - `hs_dashboard` constructor/interface PHPDoc corrections.
  - `hs_siteimprove` return-doc correction.
  - `su_humsci_profile` post-install explicit nullable return.

- Batch 3 scope:
  - Drupal subtype narrowing in hooks, services, and subscribers where broad framework interfaces hid valid method calls.
  - Menu link constraint validator request/constraint typing.

- Batch 4 scope:
  - Widget/plugin API corrections in `hs_events_importer`, `hs_field_helpers`, `hs_layouts`, `hs_dashboard` views relationship, `hs_views_helper`, and `ui_patterns_field_variants`.

- Batch 5 scope:
  - Remaining level 2 install/update typing in `hs_paragraph_types` and `su_humsci_profile`.
  - DOM element narrowing in `hs_table_filter` and its unit test.

- Level 2 validation outcome:
  - Full scan of `docroot/modules/humsci` and `docroot/profiles/humsci` is code-clean after these batches.
  - The only remaining PHPStan output at level 2 is the existing unmatched ignore pattern for `missingType.iterableValue` in `phpstan.neon`.

## Level 3

- Batch 1 scope:
  - Runtime-code property and return type mismatches in `hs_actions`, `hs_capx`, `hs_dashboard`, `hs_field_helpers`, `hs_layouts`, and `hs_migrate`.
- Batch 2 scope:
  - Test-only property typing in kernel/functional tests, primarily `$modules` covariance and one list-builder assignment type.
- Level 3 validation outcome:
  - Full scan of `docroot/modules/humsci` and `docroot/profiles/humsci` is code-clean after these batches.
  - The only remaining PHPStan output at level 3 is the existing unmatched ignore pattern for `missingType.iterableValue` in `phpstan.neon`.

## Level 4

- Batch 1 scope:
  - Production-code always-true/always-false, nullability, and redundant condition findings across `hs_blocks`, `hs_capx`, `hs_config_overrides`, `hs_config_partial`, `hs_dashboard`, `hs_events`, `hs_field_helpers`, `hs_layouts`, `hs_migrate`, and `su_humsci_profile`.
- Batch 2 scope:
  - Test-only dead code and assertion cleanup in `hs_actions`, `hs_capx`, `hs_config_readonly`, and `hs_views_helper`.
- Level 4 validation outcome:
  - Full scan of `docroot/modules/humsci` and `docroot/profiles/humsci` is fully clean.