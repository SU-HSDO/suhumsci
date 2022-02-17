# Adding a New Theme

## Basic Setup
1. Duplicate an existing `humsci_` theme directory (excluding the `humsci_basic` parent theme).
1. Update the duplicated directory name and the following files (using Colorful as an example).
    - `humsci_colorful.info.yaml` -> `humsci_new.info.yaml`
        - Update JSON within the file with the new theme information.
    - `humsci_colorful.libraries.yml` -> `humsci_new.libraries.yml`
        - No other file changes needed.
    - `humsci-colorful.theme` -> `humsci-new.theme`
        - Update all function names and references to the old theme name in the comments within the file.
    - `theme-settings.php`
        - Do not update the file name, update all function names and references to the old theme name in the comments.
  _Note: Be sure to use the naming convention where the theme name is prefixed with humsci._
1. Delete the contents of the `css` and `js` directories.
1. Confirm that the new theme is available locally and commit your changes.

## Javascript Setup
1. In the parent theme (`humsci_basic`) duplicate an existing theme directory in `src/js/`.
1. Rename the javascript file name as well as any comments within the file.
1. Any theme specific Javascript will go in this file. Shared javascript that is used in all themes is imported at the top of the file.
1. Modify `humsci_basic/webpack.config.js` to add the new theme to the JS compile task under `module.export > entry`.
1. Run `npm run build:js` to compile Javascript to the theme directory.
1. Commit the changes to the `humsci_basic` directory as well the compiled Javascript in the theme directory.

## SCSS Setup
1. In the parent theme (`humsci_basic`) duplicate an existing theme scss file in `src/scss/`.
1. In the new scss file edit line one to set `$hb-current-theme` to the new theme name. This will be the value we use throughout the scss to target our theme.
1. In `src/scss/settings/_variables.general.scss`:
    - add the new theme name to the `$hb-global-theme-list` map
    - add the new theme settings to the `$hb-spacing-list`, `$hb-animation-list`, `$hb-sidebar-list` maps.
1. Duplicate one of the themes color pairing variable files in `humsci_basic_scss_settings/`. Import the new file in `src/scss/_main.scss` under section "1. Settings" in the ITCSS structure.
1. run `npm run build:sass` to compile CSS to the new theme.
1. Commit the changes to the `humsci_basic` directory as well the compiled CSS in the theme directory.

_Note: New themes will only receive shared styles. No package.json updates necessary compiled CSS will auto compile to the new directory assuming correct naming conventions were followed._

## Backstop Updates
1. In the parent theme (`humsci_basic`) open `backstop/generate-backstop.js`.
1. Add the new theme as an item in the `sites` array. The site name must match the domain name of the test site (excluding the environment postfix).
1. Commit the changes. The new site will automatically be tested every time visual regression testing is ran.

_Note: These changes require that a new theme site has already been created on the staging and development environments. If not the regression tests will still run but the new theme pages will only show 404 errors._
