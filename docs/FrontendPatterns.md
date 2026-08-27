# Frontend Patterns

> **Audience:** Developers and AI agents writing JavaScript, SCSS, or Twig for the humsci_basic theme tree.

This document describes the frontend architecture and conventions used across the humsci_basic theme and its subthemes. Read it before adding or modifying JavaScript, Sass, or Twig templates.

## Theme Tree

- `humsci_basic` is the base theme (extends `stable9`). It owns all shared source SCSS and JS.
- `humsci_colorful`, `humsci_traditional`, and `humsci_airy` are subthemes (each declares `base theme: humsci_basic`). They hold subtheme-specific compiled CSS and a small set of subtheme libraries.
- `su_humsci_gin_admin` is the admin theme, separate from the base theme tree.

Source files live in `humsci_basic/src/`. Compiled output goes to each theme's `dist/` and `css/` directories. Never edit compiled output directly; edit the source and run the build.

## JavaScript

### Source Layout

All shared JavaScript lives in `humsci_basic/src/js/shared/`, organized by feature:

```text
src/js/shared/
  cards/          # linked-cards, structured-card, horizontal-expandable-card
  carousel-slides/
  media/
  navigation/
  tables/
  views/          # views-exposed-form, views-exposed-form-breadbox
  ...
```

Subtheme-specific JS would go in `src/js/colorful/` or `src/js/traditional/`, but most behavior is shared. Webpack entrypoints are declared in `humsci_basic/webpack.common.js`.

### Drupal Behaviors and once()

Every behavior is wrapped in an IIFE and uses `once()` so AJAX re-renders do not attach handlers multiple times:

```javascript
((Drupal, once) => {
  Drupal.behaviors.myBehavior = {
    attach(context) {
      once('my-behavior', '.my-selector', context).forEach((el) => {
        // ...
      });
    },
  };
})(Drupal, once);
```

Rules:

- Use `once('key', selector, context)`. The key must be unique per behavior.
- Prefer passing the topmost relevant element to `once()` and querying inside it.
- When elements may re-attach after AJAX, call `once.remove()` for cleanup.
- Listen for `change` on `prefers-reduced-motion` rather than checking it once at load.
- Do not `return` at the end of a function unnecessarily (ESLint flags it).

### Libraries

Each behavior is declared in `humsci_basic.libraries.yml` with `core/drupal` and `core/once` dependencies:

```yaml
linked-cards:
  js:
    dist/js/linked-cards.js: {}
  dependencies:
    - core/drupal
    - core/once
```

Attach a library with `{{ attach_library('humsci_basic/linked-cards') }}` in Twig, or declare it in a render array. Subtheme-specific libraries live in the subtheme's own `.libraries.yml`.

## Sass

### Source Layout

Partials live in `humsci_basic/src/scss/partials/`:

```text
src/scss/partials/
  _main.scss        # entry point that imports the rest
  base/             # element-level resets and defaults
  components/       # one file per component (_card.scss, _postcard.scss)
  mixins/
  functions/
  variables/
  objects/          # layout primitives (_layouts.general.scss, _layouts.row.scss)
  utilities/
  admin/
  fonts/
  ckeditor/
  preview/
```

Per-theme SCSS (variables and overrides per subtheme) lives in `src/scss/humsci_colorful/`, `src/scss/humsci_traditional/`, and `src/scss/humsci_airy/`. Webpack globs `src/scss/**/*.scss` (excluding `partials/`) as entrypoints and writes compiled CSS into each subtheme's `css/` directory.

### Conventions

- Use lowercase for hex color values.
- Avoid `!important`. Restructure selectors or use a wrapper class instead.
- Use the theme's breakpoint function for media queries, not raw pixel values.
- Use CSS variables for container-query widths and column counts.
- Add a comment explaining any non-obvious CSS override.
- Keep a newline at the end of every file.

## Preact Islands

Standalone interactive widgets are built as Preact islands, separate from the Drupal behavior stack.

- Views exposed-filter select boxes are replaced by a combobox island in `docroot/profiles/humsci/su_humsci_profile/js/select-lists/`. It is a self-contained yarn project with its own `src/` (`.tsx` files), `webpack.config.js`, and `dist/`. The island keeps the native `<select>` in sync via `option.selected`, so Drupal form submission and the `views-exposed-form` behavior both continue to work.
- `stanford_fields` ships its own Preact widget under `docroot/modules/custom/stanford_fields/js/` (`lib/` source, `dist/` output).

When modifying exposed-filter behavior, account for the combobox: the native `<select>` still exists and holds the canonical value.

## Views Exposed Filters and Auto-Submit

The exposed-form stack has three cooperating parts. Change them together, not in isolation.

1. The Preact combobox island replaces native `<select>` elements with an accessible combobox and writes the selected value back to the hidden native `<select>`.
1. Better Exposed Filters (BEF) auto-submit triggers a form submit on `change` (selects) and `input` or blur (text fields). A form is auto-submit-enabled when it has `data-bef-auto-submit`.
1. `humsci_basic` provides two behaviors: `views-exposed-form` (shows or hides the Reset button based on whether any filter is active) and `views-exposed-form-breadbox` (renders active filters as removable chips).

When adding a filter or changing submit behavior:

- The Reset button should only appear when at least one filter has a non-default value. On non-auto-submit forms, do not toggle Reset live, because the results have not refreshed yet.
- Keep the reset control inside the `<form>`. Moving it outside breaks submission.
- Trigger chip and reset updates from events, not from timers.

## Build Commands

```bash
npm run theme-build     # Compile Sass and JS for all humsci_basic-based themes
npm run theme-watch     # Compile and watch for changes
npm run theme-visreg    # Run Percy VRT on hs_colorful and hs_traditional
npm test                # Run Sass tests
```

The build auto-detects DDEV vs Lando vs bare metal. To force one, set `HSDP_COMPILE_ENVIRONMENT=ddev`, `HSDP_COMPILE_ENVIRONMENT=lando`, or `HSDP_COMPILE_ENVIRONMENT=baremetal`.

## Accessibility

- Use `visually-hidden` for screen-reader-only content. Never `display: none` or DOM removal to hide semantics.
- Set initial ARIA state in markup (`aria-expanded="false"`), not in JS. Use `aria-controls` with the `clean_unique_id` Twig filter, and prefer a visually-hidden `<span>` inside a button over `aria-label`.
- Do not override image `alt` text when wrapping an image in a link. Put the link label on the wrapping link as `aria-label` or a visually-hidden span.
- Confirm changes work with OS "reduce motion" enabled and with a screen reader where practical.

## See Also

- [Coding Standards](CodingStandards.md)
- [Testing](Testing.md)
