# Humsci Basic (humsci_basic) Theme

Humsci Basic (humsci_basic) is a theme that can be used as a base for future H&S themes. We have pulled patterns and templates for Humsci Basic from Stanford Basic (stanford_basic) Theme.

![Humsci Theme Diagrams](humsci-theme-diagram.png)

## Requirements

- Drupal 10.3.0
- Node 22

## Sub-themes

There are currently 3 children sub-themes based on Humsci Basic. Themes reference built CSS files in Humsci Basic on a per-theme basis in their `{}.libraries.yml`.

- Humsci Colorful (humsci_colorful)
- Humsci Traditional (humsci_traditional)
- Humsci Airy (humsci_airy)

> **_NOTE:_**  The Humsci Airy (humsci_airy) theme is not currently being used in any site. It is a placeholder for a potential future theme.

## Getting Started

This theme contains its own node module dependencies and build system which is separate from the root project. All commands should be run from this theme's directory.

- `npm install` - Install all node dependencies

## Builds

Frontend assets are built using the Grunt task runner, but are run using npm scripts as shortcuts. CSS assets are compiled to their respective child theme `css/` directory. JS assets are compiled to the `scripts/build/scripts.js` file.

- `npm start` - Runs the build task followed by the watch task
- `npm run build` - Compile Sass and JS for production
- `npm run watch` - Compile a CSS and JS build and watch for changes in the existing `.scss` or `.js` files

### Browserslist
In our `.browserlistrc` file we specify support for `"last 1 major version"` of each modern browser. As browsers update we need to refresh our it's a good idea to update the browserslist in the `package-lock.json` file. This is done by running `npx browserslist@latest --update-db` and committing the lock file. [More information](https://github.com/browserslist/browserslist#browsers-data-updating).

## Testing

### Linting

We use [stylelint](https://stylelint.io/) to lint all of our Sass code to maintain a consistent code style. To test it you can run: `npm run lint:sass`

Our linting rules use the [Sparkbox Stylelint Config](https://github.com/sparkbox/stylelint-config-sparkbox) as a base for our linting rules.

## Visual Regression Testing

### Tugboat Visual Diff
[Tugboat Visual Diff](https://docs.tugboatqa.com/visual-diffs/) is a visual regression tool to visually diff changes to the preview of our sites.

When a Preview is built from a Base Preview, Tugboat can generate Visual Diff images to highlight any changes between the Base Preview and the new Preview.

#### To view Visual Diffs
In order to view Tugboat’s visual diffs, you must be using at least one Base Preview.

1. Click into the link to the Visual Diff Dashboard that is included on each PR along with the tugboat links.
2. Scroll down past the Services and Lighthouse Reports, and you’ll see the Visual Diffs pane.
3. Click into the Visual Diff for Mobile, Tablet or Desktop to see the diff.

Inside the diff, you’ll see a Before visualization on the left, an After visualization on the right, and a composite in the middle, which highlights changes to the page.

You’ll also see an option to Regenerate visual diffs; use this if you’ve updated your Base Preview, and want to see a new version of the visual diffs for this build.

> **_NOTE:_**
> To configure new pages to generate Visual Diffs, you need access to Tugboat configuration. If you don't have access, please ask any of the [Tugboat manage users](https://dashboard.tugboatqa.com/5db08be544c5fa63ef0e09f2/settings/) to help you to specify the relative URLs of the pages in the service definition.

## CSS / Sass
The CSS is organized using [Harry Roberts’](https://csswizardry.com) [Inverted Triangle CSS](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/) (ITCSS) organizational approach. This method is mixed with [Block Element Modifier](http://getbem.com/) (BEM) naming convention for class names throughout the Sass files.

### Namespacing

All classes, variables, and mixins that belong to the humsci_basic theme will
be prefixed with `hb-`.
Example classes: `.hb-card`, `.hb-card--horizontal`, `.hb-card__title`.

**NOTE:** Some legacy variables, mixins are other elements that were integrated
from [Decanter](https://github.com/SU-SWS/decanter) still use the `su-` prefix.
This pattern is deprecated and will be phased out in the future.

### SCSS Structure

Compiled CSS is split in multiple files, on a _per-component_ basis. This allows to define
separate libraries and load them only when a component is displayed, to reduce CSS size and improve
performance.

Shared SCSS is located in the `src/scss/partials` folder, filenames prefixed with a `_` according to SASS
convention. Those files can be imported into the SASS files that will generate the final CSS, located
into the `src/scss/humsci_colorful`, `src/scss/humsci_traditional` and `src/scss/humsci_airy` folders.
To indicate the SASS compiler to which theme belongs each file, it's necessary to define the
`$hb-current-theme`at the top of the file:

```scss
$hb-current-theme: 'traditional';
```

Compiled CSS files are exposed to drual via libraries are defined in the `.libraries.yml` file of
each subtheme and attached to the templates of each component (or a preprocess in some exeptional cases).

The `src/scss/partials` files also contains some special files:

- `_base-imports.scss`: variables, functions and mixins needed by most components.
- `_main.scss`: base styles that will be available in all pages.
- `ckeditor/_imports.scss`: styles for CKEditor.
- `preview/_preview.scss`: base styles for the paragraph previews available in the admin theme.


| Class References                                                                      |
|---------------------------------------------------------------------------------------|
| [Prefixing of Class Names](/docroot/themes/humsci/humsci_basic/docs/css-prefixing.md) |
| [Utility Classes](/docroot/themes/humsci/humsci_basic/docs/utility-classes.md)        |
| [Sass Mixins](/docroot/themes/humsci/humsci_basic/docs/mixins.md)                     |
| [Color Pairings](/docroot/themes/humsci/humsci_basic/docs/color-pairings.md)          |
