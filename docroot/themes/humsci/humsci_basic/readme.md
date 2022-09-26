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

### Browserslist
In our `.browserlistrc` file we specify support for `"last 1 major version"` of each modern browser. As browsers update we need to refresh our it's a good idea to update the browserslist in the `package-lock.json` file. This is done by running `npx browserslist@latest --update-db` and committing the lock file. [More information](https://github.com/browserslist/browserslist#browsers-data-updating).

## Testing

- `npm test` - Run linting and sass true tests

## Visual Regression Testing

- `npm run visreg` - Runs percy script to test the visual regression of both the
Colorful and Traditional sites.

To run this, you will be required to be a member of the organization on Percy.io
and have a local Percy token. To use the Percy token, acquire it from Percy.io
under 'Project Settings'. Copy and paste the entire line under 'Project Token'
and include that in an `.env` file in this directory (`humsci_basic`).

The pages tested are located in the `.snapshots-colorful.js` and `.snapshots-traditional.js` respectively. To test the sites individually, you can run
`npm run visreg:colorful` and `npm run visreg:traditional`.

In order to have consistent testing, we need to ensure certain features are
enabled/disabled before testing. These include:
* The Colorful site should use the new V2 Megamenu
* The Traditional site should use the old V1 Megamenu
* Both Colorful and Traditional sites should have the 'Use Animation Enhancements' feature turned off on their respective theme settings.

### Sass True

We use the [Sass True](https://github.com/oddbird/true) testing framework to test our Sass function and mixins.

### Linting

We use [stylelint](https://stylelint.io/) to lint all of our Sass code to maintain a consistent code style.

Our linting rules use the [Sparkbox Stylelint Config](https://github.com/sparkbox/stylelint-config-sparkbox) as a base for our linting rules.

### Visual Regression Testing
[Backstopjs](https://github.com/garris/BackstopJS) is a CLI visual regression tool that uses headless Chrome.

The visual regression tests are run locally and used to compare what is on Staging versus what is on the Dev environment. Backstop is setup to test two identical sites (pages and content) with the only difference being the theme they use.
- [HS Colorful](https://hs-colorful.stanford.edu/)
- [HS Traditional](https://hs-traditional.stanford.edu/)

#### Initial Setup
Before running Backstop for the first time you will need to create an `.env` file in this theme directory. Copy the contents in `docroot/themes/humsci/humsci_basic/.env-sample` and place them in your `.env` file. Add the basic auth credentials.

#### Testing Prep
Visual regression testing should be completed bi-weekly at the end of each sprint. To get the best results, sync your local environment against staging before running Backstop:

1. Sync the dev environment with a copy of the staging database and files: `lando blt humsci:copy-colorful hs_colorful.dev,hs_traditional.dev`
2. Update the [hs-traditional dev](https://hs-traditional-dev.stanford.edu/) site to use the Traditional theme
3. Set the dev environment to use the sprint build branch in Acquia

#### Running Visual Regression Tests
Running the backstop tests:
1. Cd the `humsci_basic` directory.
1. Run `npm run backstop:init` to save a copy of the Backstop config to `./backtop/backstop.js`.
1. Run `npm run backstop:reference` to generate reference images, in our case reference is staging.
1. Run `npm run backstop:test` to run the tests.
1. Backstop will open an HTML page that contains the report which highlights errors.
_Note: Differences in content will also be reported as failures. Some failures can result in images not loading fully before the snapshot is taken._

#### Adding new scenarios in the `backstop/backstop.json`.
- Any new page and its content need to be present in both the [HS Colorful](https://hs-colorful-stage.stanford.edu/) and [HS Traditional](https://hs-traditional-stage.stanford.edu/) sites on the staging environment.
- Add the resource path of the new page to the `testPages` array in `backstop/generate-backstop.js`
- If adding a new theme add the theme name to the `sites` array in `backstop/generate-backstop.js`

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
