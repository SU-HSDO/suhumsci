# Code Review Feedback Analysis — `joegl`

Analysis of inline pull-request review comments left by **`joegl`** in
[SU-HSDO/suhumsci](https://github.com/SU-HSDO/suhumsci) over the **last 12 months**
(comments since 2026-06-14 — 1 year), focused on **coding-standard compliance** and
which findings are **automatable with the linting tools already installed in the repo**.

## Method

- Source: GitHub REST API `repos/SU-HSDO/suhumsci/pulls/comments` (`since` = 1 year ago),
  filtered to `user.login == "joegl"`.
- **157 inline review comments** across **31 pull requests**.
- **68** of those comments (≈43%) include a GitHub ```suggestion``` block, i.e. a concrete
  code change `joegl` is asking for — a strong signal of repeatable, mechanical feedback.

The repo's `phpcs.xml` ruleset only enables the **`Drupal`** standard, even though three
sniff libraries are installed via Composer and referenced in `installed_paths`:

- `drupal/coder` (enabled)
- `slevomat/coding-standard` (**installed, not enabled**)
- `sirbrillig/phpcs-variable-analysis` (**installed, not enabled**)

`phpstan.neon` runs at **level 5** with `mglaman/phpstan-drupal`, but currently
**ignores** the rule *"Drupal calls should be avoided in classes, use dependency injection
instead"* — which is exactly one of the recurring review themes below.

---

## 1. Most common recurring feedback

Ranked by how many separate comments touch each theme (a single PR often gets the same
note several times).

| # | Theme | Approx. count | Representative PRs |
|---|-------|--------------|--------------------|
| 1 | **Docblock / comment quality** — phrase function docs as statements not questions, drop redundant comments, add a comment describing what a block does | ~12 | 2208, 2142, 2108, 1921, 1991, 1870 |
| 2 | **Update-hook return values & metrics** — every `hook_update_N` / deploy hook should return a summarized translatable message with counts (items migrated, deleted, failed) instead of nothing or per-item noise | ~9 | 2202, 2145, 2158, 2036, 1870 |
| 3 | **Logging & error handling** — summarize logs (one message at end, not per batch), wrap risky work in `try/catch`, log failures with `\Drupal::logger()` | ~9 | 2202, 2145, 2036, 1901, 1921 |
| 4 | **Defensive field access** — call `hasField()` / `isEmpty()` before reading a field value | ~8 | 2202 (×2), 2226, 2212, 2158 |
| 5 | **Reduce nesting / early-exit guard clauses** — *"Drupal code standards typically prefer reducing nesting and improved legibility over reduced line count"*; replace nested `if`/`switch` with guard `return`/`continue` | ~8 | 1786, 2145, 2158, 1870, 2226 |
| 6 | **Batch sizing** — bump sandbox batch size (e.g. to 50) on data-migration updates | ~7 | 2145, 2158 |
| 7 | **`instanceof` checks** — verify an entity is a `ParagraphInterface`/`NodeInterface` before using it | ~5 | 2226, 2158 |
| 8 | **Strict comparison** — use `===`/`!==` and the third `TRUE` arg of `in_array()`; the `array_search() === FALSE` (vs `== false` matching index `0`) bug was an actual defect | ~5 | 2212, 2226 |
| 9 | **Return / parameter type hints** — add `: void`, `: string`, `: bool` return types and typed params; *"We should start scoping functions properly for PHP 8.4 now"* | ~6 | 1786, 1991, 2226 |
| 10 | **Dependency injection over static calls** — inject services into classes/event subscribers instead of `\Drupal::service()`; use existing services (`externalauth.authmap`, `library.discovery`) | ~3 | 1901 (×3), 2202 |
| 11 | **Translation API** — use `StringTranslationTrait` / `$this->t()` inside classes instead of the global `t()`; use placeholder/replacement patterns rather than concatenating links | ~2 | 1870, 2142 |
| 12 | **Type correctness of values** — config/array values that should be `INT` not `STRING` (`0` vs `"0"`); division-by-zero protection before `count()`-based modulo | ~3 | 2266, 2142 |

Other notable but lower-frequency notes (mostly architectural, **not** lint-automatable):
config UUID stability across sites, environment-detector misuse in update/production code,
performance (avoid executing Views when a cheaper check suffices), separation of concerns /
service naming, alphabetical ordering of `.module` includes, and safe field-storage deletion
when a field is shared across bundles.

---

## 2. Feedback that a linter could catch automatically

These recurring comments map directly onto sniffs/rules from tooling **already present**
in `vendor/`. Enabling them would offload this feedback from `joegl` to CI.

### Catchable today by enabling installed `slevomat` sniffs

The `slevomat/coding-standard` library is installed but not referenced in `phpcs.xml`.
Relevant sniffs found in `vendor/slevomat/coding-standard/SlevomatCodingStandard/Sniffs/`:

| Recurring feedback (theme #) | slevomat sniff to enable |
|------------------------------|--------------------------|
| Return type hints (#9) | `SlevomatCodingStandard.TypeHints.ReturnTypeHint` |
| Parameter type hints (#9) | `SlevomatCodingStandard.TypeHints.ParameterTypeHint` |
| Property type hints (#9) | `SlevomatCodingStandard.TypeHints.PropertyTypeHint` |
| Reduce nesting / early exit (#5) | `SlevomatCodingStandard.ControlStructures.EarlyExit` |
| Strict comparison — disallow `==`/`!=` (#8) | `SlevomatCodingStandard.Operators.DisallowEqualOperators` |
| Multi-line `if` formatting for legibility (#5) | `SlevomatCodingStandard.ControlStructures.RequireMultiLineCondition` |
| Prefer null-safe `?->` (used in several suggestions, e.g. `->first()?->entity`) | `SlevomatCodingStandard.ControlStructures.RequireNullSafeObjectOperator` |
| Prefer `??` null-coalesce (suggestion in PR 2246) | `SlevomatCodingStandard.ControlStructures.RequireNullCoalesceOperator` |

> Note: `EarlyExit` and `DisallowEqualOperators` are opinionated and will flag a large
> volume of existing code. Recommend introducing them with a regenerated `phpcs`/PHPStan
> baseline so only **new** violations fail CI.

### Catchable today by enabling installed `Drupal` Semantics sniffs / PHPStan rules

| Recurring feedback (theme #) | Tool / rule |
|------------------------------|-------------|
| Global `t()` inside classes (#11) | `drupal/coder` ships `Drupal.Semantics.FunctionT` (catches `t()` vs `$this->t()`) — already part of the `Drupal` standard; confirm it isn't excluded |
| Dependency injection over `\Drupal::` static calls (#10) | `mglaman/phpstan-drupal` rule *"Drupal calls should be avoided in classes, use dependency injection instead"* — **currently in the `ignoreErrors` list in `phpstan.neon`**; removing the ignore re-enables it |
| `INT` vs `STRING` / type mismatches (#12) | PHPStan level 5 already catches many; `phpstan/phpstan-strict-rules` would catch the `array_search() == false` (#8) and loose-comparison-of-different-types defects |
| Division by zero before modulo (#12) | PHPStan reports `Division by zero` / possibly-zero divisor at higher levels / with strict rules |
| Variable analysis (unused/undefined vars) | `sirbrillig/phpcs-variable-analysis` is installed but not enabled — enable `VariableAnalysis.CodeAnalysis.VariableAnalysis` |

### Catchable by adding a tool not yet wired up

| Recurring feedback (theme #) | Suggested addition |
|------------------------------|--------------------|
| Strict comparison defects, type-safety (#8, #12) | Add **`phpstan/phpstan-strict-rules`** to the PHPStan config |
| Type hints on legacy code (#9), if slevomat is too broad | Raise PHPStan toward **level 6** (reports missing type hints incrementally) |

---

## 3. Feedback that is NOT lint-automatable (needs human review / docs)

These are the comments most worth turning into **contributor documentation / a PR
checklist**, since no sniff will enforce them:

- **Update/deploy hooks must return a summarized translatable message with counts** (#2) —
  the single most repeated *substantive* request. Worth a documented pattern + example
  (e.g. `su_humsci_profile_update_9743` was repeatedly cited by `joegl` as the reference).
- **Summarize logging; wrap risky operations in `try/catch`; don't fail a deployment on a
  non-critical step** (#3).
- **`hasField()` / `instanceof` defensive checks before touching entity data** (#4, #7) —
  partially catchable by PHPStan's null analysis but mostly a convention.
- **Architectural notes**: dependency injection of specific services, config UUID stability,
  not using the environment detector in update/production code, safe deletion of
  bundle-shared field storage, separation of concerns, and performance (avoid executing
  Views unnecessarily).
- **Comment/docblock phrasing** (#1) — `phpcs` enforces *presence* of docblocks but not that
  they read as statements rather than questions.

---

## Recommendations

1. **Enable the already-installed slevomat sniffs** for type hints, early-exit, and strict
   comparison in `phpcs.xml`, introduced behind a fresh baseline so only new code is gated.
   This alone would automate themes #5, #8, and #9 — roughly **19 comments** in this dataset.
2. **Enable `sirbrillig/phpcs-variable-analysis`** (already installed) in `phpcs.xml`.
3. **Re-enable the PHPStan "use dependency injection" rule** by removing it from
   `ignoreErrors` in `phpstan.neon` (theme #10), and **add `phpstan/phpstan-strict-rules`**
   to catch the loose-comparison/type defects (themes #8, #12).
4. **Document the non-automatable conventions** (themes #1–#4) as a `docs/` contributor
   guide / PR checklist — especially the *update-hook return-message + metrics* pattern,
   which `joegl` requested in ~9 separate comments and is the highest-value manual rule.

---

*Generated from GitHub PR review data; 157 comments / 31 PRs by `joegl` over the trailing year.*
