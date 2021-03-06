# Humsci Basic (humsci_basic) Theme

Humsci Basic (humsci_basic) is a theme that can be used as a base for future H&S themes. We have pulled patterns and templates for Humsci Basic from Stanford Basic (stanford_basic) and Stanford University Humsci Theme (su_humsci_theme) Themes. Humsci Basic integrates selectively with Decanter (v6) Sass, styles, and patterns.

![Humsci Theme Diagrams](humsci-theme-diagram.png)

## Requirements

- Drupal 8.7.10
- Node 10.15+

## Sub-themes

There are currently 3 children sub-themes based on Humsci Basic. Themes reference built CSS files in Humsci Basic on a per-theme basis in their `{}.libraries.yml`.

- Humsci Colorful (humsci_colorful)
- Humsci Traditional (humsci_traditional)
- Humsci Airy (humsci_airy)

## Getting Started

This theme contains its own node module dependencies and build system which is separate from the root project. All commands should be run from this theme's directory.

- `npm install` - Install all node dependencies

## Builds

Frontend assets are built using the Grunt task runner, but are run using npm scripts as shortcuts. CSS assets are compiled to their respective child theme `css/` directory. JS assets are compiled to the `scripts/build/scripts.js` file.

- `npm start` - Runs the build task followed by the watch task
- `npm run build` - Compile Sass  and JS for production
- `npm run watch` - Compile a CSS and JS build and watch for changes in the existing `.scss` or `.js` files

## Testing

- `npm test` - Run linting and sass true tests

### Sass True

We use the [Sass True](https://github.com/oddbird/true) testing framework to test our Sass function and mixins.

### Linting

We use [stylelint](https://stylelint.io/) to lint all of our Sass code to maintain a consistent code style.

Our linting rules use the [Sparkbox Stylelint Config](https://github.com/sparkbox/stylelint-config-sparkbox) as a base for our linting rules.

### Visual Regression Testing
[Backstopjs](https://github.com/garris/BackstopJS) is a CLI visual regression tool that uses headless Chrome.

The visual regression tests are run locally and used to compare what is on Production versus what is on your local machine.

1. Add new scenarios in the `backstop/backstop.json`. The format should look like the following:
```
"scenarios": [
  {
    "label": "Customize site",
    "url": "http://swshumsci.suhumsci.loc/site-building/customize-site",
    "referenceUrl": "https://swshumsci-prod.stanford.edu/site-building/customize-site",
    "delay": 0,
    "requireSameDimensions": true
  }
]
```
1. Run `npm run backstop:init` to generate reference images, in our case reference is production:
1. Run `npm run backstop:test` to run the tests.
1. Backstop will open an HTML page that contains the report which highlights errors.
_Note: Differences in content will also be reported as failures._

### Behavioral tests

This codebase uses Behat to provide behavioral testing for all themes used in production, including `humsci_basic` themes.

Once you have set up your config, you run these tests locally with Lando, like so:

```bash
# Runs all @global tests, excluding @javascript tests
lando behat --tags='@global&&~@javascript'
```

For more info, (including Lando setup details) see [these Behat testing notes](https://docs.google.com/document/d/11lEDdzDk5CYMKoXAON05LIlfmzbdk00tex6DNdo-U74/edit?ts=5eb32acf).

## Contributing
### Github
To make it easier to find work being done we should use the following naming conventions:

### Branch Names:
`STN-XXX--descriptive-message`

#### Commit Messages:
`feat(STN-XXX): descriptive message shorter than 80 chars`
`fix(STN-XXX): descriptive message shorter than 80 chars`
`refactor(STN-XXX): descriptive message shorter than 80 chars`
`chore(STN-XXX): descriptive message shorter than 80 chars`
`docs(STN-XXX): descriptive message shorter than 80 chars`

https://www.conventionalcommits.org/en/v1.0.0-beta.2/

#### Pull Request Titles:
`STN-XXX: Short Descriptive Titles`

Pull request descriptions should follow the PR template that is generated when creating a new commit.

#### Green Button Merging:
After receiving a review and getting a PR approved, we do green-button merges for our PRs ("Rebase and Merge") because Github includes a link to the PR in our commit message header.

## Decanter Integration

This theme aims to **partially** integrate [Decanter](https://github.com/SU-SWS/decanter). Instead of rendering all the styles generated by Decanter we:

- Import variables, function and mixins
- Compile various helpers classes
- Compile specific components such as Brand Bar, Logo, Lockup and Footer

## CSS / Sass
The CSS is organized using [Harry Roberts’](https://csswizardry.com) [Inverted Triangle CSS](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/) (ITCSS) organizational approach. This method is mixed with [Block Element Modifier](http://getbem.com/) (BEM) naming convention for class names throughout the Sass files.

| Class References                                                                      |
|---------------------------------------------------------------------------------------|
| [Prefixing of Class Names](/docroot/themes/humsci/humsci_basic/docs/css-prefixing.md) |
| [Utility Classes](/docroot/themes/humsci/humsci_basic/docs/utility-classes.md)        |
| [Sass Mixins](/docroot/themes/humsci/humsci_basic/docs/mixins.md)                     |
| [Color Pairings](/docroot/themes/humsci/humsci_basic/docs/color-pairings.md)          |
