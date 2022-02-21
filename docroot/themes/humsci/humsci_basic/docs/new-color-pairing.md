# Adding a New Color Pairing

1. Create a new settings partial with color pairings and globals SCSS maps
    - Use a theme specific prefix for SCSS maps and utility classes
    - Define a default color pairing variable
1. Define the custom properties for the theme `:root` in `elements/_base.scss` partial
1. Define fallback colors in `tools/_mixins.color-pairings.scss`
    - Mixin for pairing colors
    - Mixin for global colors
1. Generate color pairing utility classes in `utilities/_color-pairings.scss`
