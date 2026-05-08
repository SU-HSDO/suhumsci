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