## Color Pairings

A single theme can have multiple color pairings. The default pairing for the Colorful Theme is Ocean, and for the Traditional Theme is Cardinal.

Color palettes for each theme are set in `/humsci_basic/src/scss/settings/_variables.colorful-pairings.scss` and `/humsci_basic/src/scss/settings/_variables.traditional-pairings.scss`.

A user can update the color pairing in the Drupal admin by going to Appearance / Settings / Humsci Colorful (or Humsci Traditional). Under **Theme Specific Settings** you will find **Color Pairing** options. The theme setting affixes a color pairing class to the `<html>` element which is used to determine the values rendered in the CSS custom properties (variables).

### Colorful Theme

| Name     | Class                |
|----------|----------------------|
| Ocean    | hc-pairing-ocean     |
| Mountain | hc-pairing-mountain  |
| Cardinal | hc-pairing-cardinal  |
| Lake     | hc-pairing-lake      |
| Canyon   | hc-pairing-canyon    |
| Cliff    | hc-pairing-cliff     |

### Traditional Theme

| Name     | Class                |
|----------|----------------------|
| Cardinal | ht-pairing-cardinal  |
| Blue Jay | ht-pairing-bluejay   |
| Warbler  | ht-pairing-warbler   |
| Firefinch| ht-pairing-firefinch |
| Vireo| ht-pairing-vireo |
