## Sass Mixins

Sass Mixins allow us to write clean, consistent code by defining styles that can be re-used throughout the project. Mixins are found under the `/src/scss/tools` directory.

| Mixins |
|--------|
| Buttons |
| Forms |
| [General](#general) |
| Icons |
| Layout |
| Links |
| Lists |
| Menu Icons |
| Tables |
| Text |
| [Themes](#themes) |

### General

There are several general mixins within the project for tasks such as visually hiding content, clearing floats, etc. Only unique mixins that are specific to the Humsci Basic themes are listed below.

| Mixin | Description | Example |
|-------|-------------|---------|
| @mixin pseudo-background-box($color, $height: 100%, $width: 100%) { } | Adds a background box pseudo element | <code>.example { @include pseudo-background-box('secondary', 50%, 8.75%); }</code> |
| @mixin hb-well { } | Applies theme specific background styles for well components | <code>.example { @include hb-well; } </code> |

### Themes

| Mixin | Description | Example |
|-------|-------------|---------|
| @mixin hb-themes($theme-list) { } | Applies styles to multiple themes | <code>.example { @include hb-themes(('airy', 'colorful')) { display: block; }}</code> |
| @mixin hb-colorful() { } | Applies styles to the Colorful theme | <code>.example { @include hb-colorful { display: block; }}</code> |
| @mixin hb-traditional() { } | Applies styles to the Traditional theme | <code>.example { @include hb-traditional { display: inline; }}</code> |
| @mixin hb-airy() { } | Applies styles to the Airy theme | <code>.example { @include hb-airy { display: flex; }}</code> |
