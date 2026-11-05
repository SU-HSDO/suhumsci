# Conditional Block Visibility

> **Audience:** Written for the H&S web team using Layout Builder, and for developers extending the `hs_blocks` module.

## Overview

An editor can attach one visibility rule to any Layout Builder block. The rule checks a field on the content being displayed and hides the block when it matches. This replaces the hidden-view-block workaround for showing a block only when a field has a value.

Rules work on blocks placed directly in a section and on blocks nested inside a group block. When a rule hides every block in a group block, the group block hides itself.

## Setting a Rule

1. Open the layout for the content (a node's **Layout** tab, or a content type's default layout).
1. Hover over the block, open the contextual menu, and choose **Manage visibility**.
1. Choose a **Field**, then choose when to hide the block.
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

## Layout Builder Preview

The rule never hides a block while editing a layout. A block that the rule would hide on the live page is shown with a dashed outline and a "Conditionally hidden" label, so you can still reach its contextual links.

## How Dates Are Compared

- **Date and time fields** are stored in UTC and compared as exact moments.
- **Date-only fields** have no time, so the date starts at midnight in the site timezone (set at **Configuration > Regional and Language > Regional Settings**).
- **Date range and smart date fields** are compared by their end, so an event counts as past once it has ended. A date-only range end lasts until the end of that day. For a recurring smart date, only the first occurrence stored in the field is compared.
- **Fields with several values** match "is in the future" when any value is still ahead, and "is in the past" when none is. The two rules are always opposites.
- **An empty date field** never hides the block with either date rule.
- A date equal to the current time counts as past.

## Caching

The block is rebuilt whenever the content changes, because the rule adds the entity's cache tags. For date rules, the block's cache lifetime ends at the next date that changes the result, so the block appears or disappears when the date passes without a cache clear. Page caching, including Acquia's Varnish layer, respects this lifetime.

> **Note:** Because of the shortened lifetime, a page with a date rule is cached only until the nearest upcoming date. Pages whose dates are all in the past, or have no value, keep the normal cache lifetime.

## Behavior with Invalid Rules

A rule that cannot be evaluated never hides a block. This covers a field that was deleted, a bundle that does not have the field, an unknown operator, and a date rule on a field that is not a date field.

## Where the Code Lives

All code is in the `hs_blocks` module (`docroot/modules/humsci/hs_blocks/`).

| What | Where |
|---|---|
| Rule evaluation and cache metadata | `src/VisibilityRuleEvaluator.php` (service `hs_blocks.visibility_rule_evaluator`) |
| Hiding the block on the live page and flagging it in the preview | `src/EventSubscriber/VisibilityRuleSubscriber.php` |
| The **Manage visibility** form | `src/Form/HsManageBlockVisibilityForm.php` |
| Routes and contextual links | `hs_blocks.routing.yml`, `hs_blocks.links.contextual.yml` |

### Stored Data

The rule is stored on the Layout Builder component, next to the component attributes:

```yaml
visibility_rule:
  field: field_hs_person_title_override
  operator: not_empty # empty, not_empty, future, or past
  action: hide
```

For a block nested in a group block, the rule is stored under `children.<UUID>.additional` in the group block's configuration. The form writes both locations.

### Permissions

The form and its routes use the `configure any layout` permission, the same as the other `hs_blocks` Layout Builder routes.

## Limitations

- One rule per block.
- The only action is hide. There is no "show if" rule.
- Rules read fields of the entity being displayed. They cannot check other entities, the current user, or the request.
- The visible preview label is added with CSS. Adjust `scss/hs_blocks.visibility.admin.scss` and recompile to change it.
