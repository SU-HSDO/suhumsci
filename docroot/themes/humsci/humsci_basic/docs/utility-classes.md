## Utility Classes

### Field Utility Classes
Field Utility Classes are used to apply styles to a field in a view (Drupal Home / Administration / Structure / Views) or to a field within a block in layout builder.

| Class                | Description         |
|----------------------|---------------------|
| hb-title             | Applies title styles to any heading (adds bar above for Colorful theme). Should not be used when hb-categories is used above the title. |
| hb-heading-1         | Applies heading 1 styles |
| hb-heading-2         | Applies heading 2 styles |
| hb-heading-3         | Applies heading 3 styles |
| hb-heading-4         | Applies heading 4 styles |
| hb-heading-5         | Applies heading 5 styles |
| hb-heading-6         | Applies heading 6 styles |
| hb-body-small        | Applies the small body style |
| hb-body-medium       | Applies the medium body style |
| hb-link              | Applies the link style |
| hb-link-inline       | Applies the inline link style |
| hb-descriptor        | Applies the descriptor style |
| hb-subtitle          | Applies the subtitle style |
| hb-blockquote        | Applies the blockquote style |
| hb-serif             | Applies the serif font style (for the Traditional Theme only)|
| hb-text-align-left   | Aligns text to the left |
| hb-text-align-center | Aligns text in the center |
| hb-text-align-right  | Aligns text to the right |
| hb-divider           | Applies a divider line below any field |
| hb-highlighted-label | Applies the highlighted style to any label |
| hb-pill              | Applies the pill style |
| hb-categories        | Applies the categories style to any field with multiple items (can be linked) |
| hb-pill-list         | Applies the pill style to any field with multiple items |
| hb-pill-link-list    | Applies the pill link style to any field with multiple items that are links |
<br>

### Group Block Utility Classes
Group Block Utility Classes are used to apply styles to node group blocks within the layout builder.

| Class                     | Description                    |
|---------------------------|--------------------------------|
| hb-well                   | Adds a background to the block |
| hb-borderless             | Adds a background to the block without a border |
| hb-columns                | Puts fields inside the block into two columns |
| hb-inline                 | Puts fields inside the block inline |
| hb-inline-pipe            | Puts fields inside the block inline with pipes as spacers |
| hb-main-body-detail-image | Used when images on detail pages are moved into the main body section. |
<br>

### WYSIWYG Platform Wide Text Area Classes
| Class |
|-------|
| hs-font-splash |
| hs-font-lead |
| hs-short-line-length |
| hs-caption |
| hs-credits |
| hs-button |
| hs-button--big |
| hs-seocondary-button |
| hs-external-link |
| hs-private-link |
| hs-mailto-link |
| hs-emphasized-text |
| hs-more-link |
| hs-table--borderless |
| hs-well |
<br>


### Views
| Class     | Description                                                                |
|-----------|----------------------------------------------------------------------------|
| hb-views-divider | Adds a line beneath the view (except the last row), goes 100% width |

#### Card Image Widths in Views
All images in card patterns have default widths defined:
* 100% (12 columns) for vertical cards
* 30% ( 5 columns) for horizontal and structured cards (starting at the md breakpoint)
These styles can be overridden by adding utility classes to the entire view. `hb-card-image-*` classes follow a similar naming convention to the [Decanter Grid System](https://decanter.stanford.edu/page/layouts-grid-system/), including the responsive names.

Classes are made responsive by adding the desired breakpoint (xs, sm, md, lg, xl, 2xl) after the `hb-card-image-`. **Example:** `hb-card-image-sm-4-of-12`

For best practice, you might just want to use one responsive class if you'd like mobile styles to keep their defaults. To do this, you would just change an image's sytles on the medium breakpoint. **Example:** `hb-card-image-md-4-of-12`

| Class                 |
|-----------------------|
| hb-card-image-1-of-12 |
| hb-card-image-2-of-12 |
| hb-card-image-3-of-12 |
| hb-card-image-4-of-12 |
| hb-card-image-5-of-12 |
| hb-card-image-6-of-12 |
| hb-card-image-7-of-12 |
| hb-card-image-8-of-12 |
| hb-card-image-9-of-12 |
| hb-card-image-10-of-12 |
| hb-card-image-11-of-12 |
| hb-card-image-12-of-12 |
<br>

### Table Column Widths
A set of utility classes that can added to a field in the table view.

| Class                |
|----------------------|
| hb-table-col-1-of-12 |
| hb-table-col-2-of-12 |
| hb-table-col-3-of-12 |
| hb-table-col-4-of-12 |
| hb-table-col-5-of-12 |
| hb-table-col-6-of-12 |
| hb-table-col-7-of-12 |
| hb-table-col-8-of-12 |
| hb-table-col-9-of-12 |
| hb-table-col-10-of-12 |
| hb-table-col-11-of-12 |
| hb-table-col-12-of-12 |

Classes are made responsive by adding the desired breakpoint breakpoint (xs, sm, md, lg, xl, 2xl) after ` hb-table-col-`. **Example:** ` hb-table-col-sm-4-of-12`

_Note:_ Classes must be added to the field only. Any classes added to the field and label will not be included in the markup. Field classes are added to the `td` element and semantically there cannot be another element wrapped around single `td` elements in order to add a wrapper class.

* This does not work for table patterns (neither table, nor table row)
<br>

### Layout Builder "Components" region
| Class                            | Description                                                                           |
|----------------------------------|---------------------------------------------------------------------------------------|
| hb-raised-cards                  | Adds a drop shadow behind any Card UI pattern used in the view |
| hb-stretch-vertical-linked-cards | Stretches all vertical linked cards on a page to be the same height and adjusts the height of all card titles to be the same height |
| hb-display-more-link-text | Displays helper link text with more link styling |
