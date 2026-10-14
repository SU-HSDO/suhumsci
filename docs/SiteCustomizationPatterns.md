# Site Customization Patterns

> **Audience:** Written for the H&S web team customizing an individual site or testing how a change can affect specific sites, and for AI assistants helping implement or test a site-level customization request.

HSDP ships a shared set of content types, fields, views, and theme components to every site (the "product"). Individual sites regularly need something the product doesn't provide: an extra field on a person record, a one-off content type, a tweaked card layout, a bit of CSS. This document catalogs the supported ways site owners customize a site without forking the platform, with a real example of each pattern.

## How Site-Level Customization Is Kept Separate from the Product

Every pattern in this document relies on the same underlying mechanism, described in full in [Configuration Management](Config.md):

- Product-level configuration (content types, fields, views, etc. shipped by this codebase) uses an `hs_` prefix and lives under `docroot/modules/humsci/` or `config/default/`.
- `hs_config_prefix` automatically prefixes any config a site owner creates through the admin UI with `custm_`, so it's visibly distinct from product config.
- `config_ignore` keeps `custm_`-prefixed and other site-specific config out of config import/export, so a platform-wide config deployment never touches or deletes it.

Because of this, a site-level customization never appears in this repository. To see what a given site has customized, you have to look at that site directly (its local database after a sync, or its live admin UI).

## Custom Content Types

Site owners can create an entirely new content type through **Structure > Content Types > Add Content Type**, when none of the shipped content types (Page, News, Event, Person, etc.) fit the content being modeled. The new content type's machine name gets the `custm_` prefix automatically.

**Example:** `jrbp2025` defines a `Project` content type (machine name `custm_project`), described as "A Project is used for publishing academic, research, or student projects." Editors add one at `/node/add/custm_project`.

Use this pattern when the content doesn't fit any shipped bundle and needs its own fields, display, and listing. Because the content type's schema lives only in that site's database, document any site-level content type's fields and purpose somewhere the site owner can find later. This repository can't show it.

## Custom Fields on Default Content Types

Site owners can add fields to an existing shipped content type through **Structure > Content Types > [type] > Manage Fields**, without creating a module or touching code. New fields also get the `custm_` prefix.

**Example:** `jrbp2025` extends the shipped People (`hs_person`) content type with over 40 `custm_`-prefixed fields (`custm_emergency_contact_name`, `custm_lab_pi`, `custm_driver_authorization_form`, `custm_date_harassment_training_c`, and others) to track research-compliance and volunteer information on imported people records, well beyond the directory information the shipped People fields cover. Manage at `/admin/structure/types/manage/hs_person/fields`.

Use this pattern when you need to store more data on an entity that already has the right content type and display, rather than modeling a new content type from scratch.

## Theme Settings

The Colorful and Traditional themes expose a set of site-level appearance settings at **Appearance > Settings > [theme name]**. Most of these are already documented in the theme's own docs; this section only indexes them.

| Setting | What It Controls | Reference |
|---|---|---|
| Color Pairing | Which color palette class (e.g. Ocean, Mountain, Cardinal) is applied to the site | [Color Pairings](../docroot/themes/humsci/humsci_basic/docs/color-pairings.md) |
| Lockup Style | The Stanford identity lockup's layout and text lines in the masthead | [Lockup Styles](../docroot/themes/humsci/humsci_basic/docs/lockup-styles.md) |
| Dark Inversion | A dark color variant applied globally via a class on `<html>` | [Dark Inversion](../docroot/themes/humsci/humsci_basic/docs/dark-inversion.md) |
| Mega Menu | Enables the expanded desktop navigation menu | [HS Mega Menu Usage](../docroot/modules/humsci/hs_megamenu/readme.md) |
| Brand Bar | The variant class applied to the brand bar above the masthead | Set in `humsci_basic_brand_bar` in `theme-settings.php`; not yet documented beyond the settings form |
| Animation | Enables or disables motion/animation enhancements sitewide, useful for meeting WCAG 2.1 AAA | Set in `humsci_basic_animation_enhancement` in `theme-settings.php`; not yet documented beyond the settings form |


## Utility Classes

The theme ships a large set of CSS utility classes for adjusting spacing, card layout, dark-pattern inversion, and similar presentation details without writing custom CSS. The full catalog is in [Utility Classes](../docroot/themes/humsci/humsci_basic/docs/utility-classes.md).

These classes get applied to markup through whatever "CSS class" field the context provides:

- A block's **CSS class** field (under Advanced) when configuring a block in Layout Builder
- A Views field's **Style Settings > CSS class** (rewrite) option, or a display's own **CSS class** option
- Directly in a WYSIWYG field's source mode

## Layout Builder Customization

Content types with Layout Builder enabled let a site owner override the layout on an individual node, or adjust the default layout for every node of that type, by placing blocks into regions and applying utility classes to them.

### Utility Classes on Content Type Displays

When you add or edit a field block in Layout Builder, its configuration form includes a **Field Formatter Class** field. A class entered there is added as a wrapper class around that field's rendered output, for that block only, without affecting the field anywhere else it's displayed.

**Example:** On a Person detail page layout, the "Email" field block inside a "Contact" block group has its **Field Formatter Class** set to `hb-highlighted-label`, a utility class from [Utility Classes](../docroot/themes/humsci/humsci_basic/docs/utility-classes.md), applied only to that field's placement in that group.

### Views Blocks in Layout Builder

A view's block display can be placed directly into a Layout Builder region. Because the block renders whatever the view's own filters produce, a view can control its own visibility without any extra Layout Builder configuration.

**Example:** The shipped `hs_default_events` view includes a block display named "RSVP Event Button," filtered to the current node (`nid` argument) with a date filter of `field_hs_event_date_value >= now`. This block is placed in the `top` region of the Event (`hs_event`) content type's default Layout Builder display. Once an event's date passes, the filter excludes it, the view returns no rows, and the block renders nothing, with no separate visibility condition or custom code required.

When a Views block is placed in Layout Builder, each field inside it can be reconfigured from that block's own "Configure field" dialog. The **For** dropdown at the top of that dialog chooses the scope of the change: **This block (override)** applies it only to this one placement, while the other option applies it everywhere the block appears. This lets a site owner reuse one view in multiple places but customize its rewrite or markup on only one page, without duplicating the view.

## Views Customizations

### Combining Fields with a Views Pattern

The `ui_patterns_views` module adds a "Pattern" row plugin to Views, which maps a view's fields onto the named slots of a UI Patterns component (card, timeline, testimonial, etc.) instead of rendering them as a flat field list. This is how a view produces the same card/pattern markup used elsewhere on the site.

**Example:** The `custm_polisci_people` view on `politicalscience` uses the `structured_card` pattern, mapping `field_hs_person_image` to the pattern's `image` slot, `title` to `title`, `field_hs_person_title` to `subtitle`, and `field_hs_person_interests` to `category`. Configure at `/admin/structure/views/view/custm_polisci_people/edit/block_1`, under the row style (Format > Show > Pattern).

### Utility Classes and Rewrites in Views

A view's field-level **CSS class** setting (under Style Settings) and a display's own **CSS class** setting apply the utility classes from [Utility Classes](../docroot/themes/humsci/humsci_basic/docs/utility-classes.md) directly to that view's markup, without a custom template.

**Example:** In the `custm_polisci_people` view, the `field_hs_person_title` and `field_hs_person_interests` fields use the element classes `hb-subtitle` and `hb-categories`, and the block display itself sets its CSS class to `hb-card-image-md-2-of-12 hb-card-image-4-of-12` to control the card image's responsive width. Configure these under each field's Style Settings, or the display's **Other > CSS class** setting, at `/admin/structure/views/view/custm_polisci_people/edit/block_1`.

A field's **Rewrite Results** option goes further: toggling "Override the output of this field with custom text" replaces the field's rendered output with arbitrary HTML and Twig, including the field's own token.

**Example:** The Telephone field on a PoliSci People block rewrites its output to add a Font Awesome icon ahead of the value:

```twig
<div><i class="fas fa-phone"></i>&nbsp;&nbsp;{{ field_hs_person_telephone }}</div>
```

Use a CSS class when an existing utility class covers the need. Reach for a rewrite when the markup itself needs to change, such as adding an icon or wrapping the value in different tags, and no formatter or utility class already does it.

## Asset Injector Custom CSS

The contributed Asset Injector module, available at **Configuration > Content Authoring > Asset Injector**, lets a site owner add CSS (or JS) directly through the admin UI, scoped to that site, without a code deploy. Injector config entities also get the `custm_` prefix via `hs_config_prefix`.

**Example:** The `hs_traditional` site's `custm_lockup` CSS injector overrides lockup line sizing and hides the identity logo cell on narrow screens:

```css
.su-lockup__line1 {
  font-size: 2.4rem;
  font-weight: 600;
}

@media only screen and (max-width: 480px) {
  .su-lockup__cell1 {
    display: none;
    border: none;
    text-align: left;
  }
}
```

**If possible, avoid using Asset Injector**. Prefer an existing utility class or theme setting first; reach for Asset Injector only when neither covers the need and only for small, site-specific CSS adjustments that don't belong in the shared theme.

### Recognize a Repeated Need Instead of Copying CSS

An audit of Asset Injector rules across a sample of sites found that most injected CSS falls into a few recurring categories, not one-off page design. Before writing new injected CSS, check whether the need matches one of these, since a need that shows up on more than one site is a sign it belongs in the platform instead:

- **A theme setting that doesn't exist yet.** The lockup typography adjustment shown above was found, in slightly different wording, on several other sites. Because it is really a theme-level preference rather than a page-specific fix, this kind of need is a candidate for a new option on the theme's settings screen (see [Theme Settings](#theme-settings)) rather than a CSS injector on each site.
- **A component option that doesn't exist yet.** Turning off automatic hyphenation on a card title, or adding a border to a table, surfaced on multiple unrelated sites. These are candidates for a new field on the component's own configuration form.
- **A sitewide admin-UI preference**, such as hiding an option from the contextual edit menu. These fit better as a global permission or setting than as CSS repeated site by site, since they are an editing-interface decision, not a page design.
- **A one-off brand color.** A hardcoded background, text, or border color for a single site's hero, testimonial, or footer is better served by picking or adding a design-system colorway than by hardcoding the color in CSS.
- **Dead code.** CSS injectors accumulate over a site's lifetime. A rule whose selector no longer matches anything on the current theme or page (because the component it targeted was redesigned or removed) should be deleted rather than carried forward.

If a CSS need is genuinely specific to one site's content and doesn't fit any of the above, Asset Injector remains the right tool.

> **Note:** Injected CSS is not visible in this repository (`docroot/sites/*/files` is gitignored). To see a site's current Asset Injector CSS, sync the site locally and look in `docroot/sites/<SITENAME>/files/asset_injector/css/`, or view it directly in that site's admin UI.

## Choosing a Pattern

| Need | Pattern |
|---|---|
| A kind of content no shipped content type models | Custom Content Type |
| More data on an existing content type | Custom Field |
| A different color palette, lockup layout, or navigation style | Theme Setting |
| Spacing, card, or layout adjustments covered by an existing class | Utility Class |
| A block that should only appear under certain data conditions | Views Block in Layout Builder, filtered appropriately |
| A view's fields rendered as a card, timeline, or other pattern | Views Pattern (`ui_patterns_views`) |
| A small CSS rule with no matching utility class | Asset Injector |

## See Also

- [Configuration Management](Config.md), for how `hs_config_prefix`, `config_ignore`, and `hs_config_partial` keep site-level config separate from the product
- [Humsci Basic Documentation](../docroot/themes/humsci/humsci_basic/docs/README.md), for the full theme SCSS, class, and settings reference
- [HS Mega Menu Usage](../docroot/modules/humsci/hs_megamenu/readme.md)
