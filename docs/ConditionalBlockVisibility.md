# Conditional Block Visibility

> **Audience:** Written for the H&S web team using Layout Builder, and for developers extending the `hs_blocks` module.

## Overview

An editor can attach one visibility rule to any Layout Builder block. The rule checks a field on the content being displayed and hides the block when it matches. This replaces the hidden-view-block workaround for showing a block only when a field has a value.

Rules work on blocks placed directly in a section and on blocks nested inside a group block. When a rule hides every block in a group block, the group block hides itself.

## Setting a Rule

1. Open the layout for the content (a node's **Layout** tab, or a content type's default layout).
1. Hover over the block, open the contextual menu, and choose **Manage visibility**.
1. Choose a **Field**, then choose when to hide the block.
1. For a date range or event (smart date) field, choose **Compare using** → **Start date** or **End date**.
1. Select **Update**, then save the layout.

To remove a rule, reopen **Manage visibility**, choose **Always show this block**, and select **Update**.

The **Field** list contains the fields added to the content type, plus the title. Internal fields such as the ID, UUID and revision data are not listed.

### Available Rules

| Rule | Hides the block when | Field types |
|---|---|---|
| Is empty | The field has no value | Any listed field |
| Is not empty | The field has a value | Any listed field |
| Is in the future | The date has not passed yet | Date fields only |
| Is in the past | The date has passed | Date fields only |

Date fields are fields of type date and time (`datetime`), date range (`daterange`), smart date (`smartdate`, used by the event date), or timestamp (`timestamp`). The form groups them under **Date fields** and rejects a date rule on any other field.

The **Hide this block if the field** list adapts to the chosen field: every field offers *is empty* and *is not empty*, and date fields also offer *is in the future* and *is in the past*. The **Compare using** start/end choice only appears for date range and event (smart date) fields.

Date range and event (smart date) fields have both a start and an end, so the form adds a **Compare using** option. For example, to hide an event's RSVP block the moment the event begins, choose the field, set **Compare using** to **Start date**, and choose **is in the past** — the block shows until the start time (to the minute) and hides once it passes.

## Layout Builder Preview

The rule never hides a block while editing a layout. A block that the rule would hide on the live page is shown with a dashed outline and a "Conditionally hidden" label, so you can still reach its contextual links.

## How Dates Are Compared

- **Date and time fields** are stored in UTC and compared as exact moments.
- **Date-only fields** have no time, so the date starts at midnight in the site timezone (set at **Configuration > Regional and Language > Regional Settings**).
- **Date range and smart date fields** are compared by the boundary chosen in **Compare using**, defaulting to the end. Comparing the end means an event counts as past once it has ended; comparing the start means it counts as past once it has begun. A date-only range end lasts until the end of that day. A recurring smart date stores each occurrence as a separate value, so all of them are compared, following the several-values rule below.
- **Fields with several values** match "is in the future" when any value is still ahead, and "is in the past" when none is. The two rules are always opposites.
- **An empty date field** never hides the block with either date rule.
- A date equal to the current time counts as past.

## Caching

The block is rebuilt whenever the content changes, because the rule adds the entity's cache tags. For date rules, the block's cache lifetime ends at the next date that changes the result, so the block appears or disappears when the date passes.

Drupal's own page and dynamic page caches honor this shortened lifetime directly. Acquia's Varnish layer does not: it caches anonymous pages for the fixed page lifetime (`system.performance`'s page cache max-age) and is only cleared when a cache tag is invalidated. To keep date rules working behind Varnish, the module records the next flip time for each affected entity as its page is built, and `hs_blocks_cron()` invalidates those entities' cache tags once the time passes, purging Varnish. The flip therefore happens on the next cron run after the date, not at the exact moment.

> **Note:** Pages whose date rules are all in the past, or have no value, keep the normal cache lifetime and record nothing.

## Behavior with Invalid Rules

A rule that cannot be evaluated never hides a block. This covers a field that was deleted, a bundle that does not have the field, an unknown operator, and a date rule on a field that is not a date field.

## Where the Code Lives

All code is in the `hs_blocks` module (`docroot/modules/humsci/hs_blocks/`).

| What | Where |
|---|---|
| Rule evaluation and cache metadata | `src/VisibilityRuleEvaluator.php` (service `hs_blocks.visibility_rule_evaluator`) |
| Hiding the block on the live page and flagging it in the preview | `src/EventSubscriber/VisibilityRuleSubscriber.php` |
| Cron invalidation of date rules behind Varnish | `src/VisibilityFlipTracker.php` (service `hs_blocks.visibility_flip_tracker`), `hs_blocks_cron()` |
| The **Manage visibility** form | `src/Form/HsManageBlockVisibilityForm.php` |
| Routes and contextual links | `hs_blocks.routing.yml`, `hs_blocks.links.contextual.yml` |

### Stored Data

The rule is stored on the Layout Builder component, next to the component attributes:

```yaml
visibility_rule:
  field: field_hs_person_title_override
  operator: not_empty # empty, not_empty, future, or past
  # boundary (start or end) is added only for a future/past rule on a
  # date range or smart date field; it defaults to end.
  boundary: end
```

For a block nested in a group block, the rule is stored under `children.<UUID>.additional` in the group block's configuration. The form writes both locations.

### Permissions

The **Manage visibility** link on a top-level block is gated on Layout Builder access only (`_layout_builder_access: 'view'`), the same as core's own block links, so editors with per-bundle override permissions also see it. The link on a block nested in a group block uses the `configure any layout` permission, the same as the other group-block links.

## Limitations

- One rule per block.
- The only action is hide. There is no "show if" rule.
- Behind Varnish, a date rule flips on the next cron run after the date, not at the exact time.
- Rules read fields of the entity being displayed. They cannot check other entities, the current user, or the request.
- The visible preview label is added with CSS. Adjust `scss/hs_blocks.visibility.admin.scss` and recompile to change it.
