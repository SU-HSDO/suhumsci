# 2. Expose some metatags to editors

## Status

Accepted

## Context

We want to allow editors to change a short list of metatags.

If you’ve ever enabled the metatag field in the sidebar of the node form, you know how overwhelming it is.  There’s 500 fields, but you’ll only ever touch 2–5 of them.

### Paths considered

#### Hiding the fields we don't want them to see

This could work.  But even the surrounding help text is just not quite good
enough for our purposes.  We'd be hiding or overriding 99% of what's
out-of-the-box.

#### Enabling the Metags Permissions module

This module exposes permissions for each single metatag.  Multiplied by the
number of roles means that there are now hundreds (a thousand?) more checkboxes.
It's both a cognitive overload, and prone to cause timeouts.  Plus we'd still
need to form alter to tweak the help text.

#### Fields
Use Field API to add fields to our node types for each metatag.  Then write a presave hook to programmatically update the metatags accordingly.  We'd have
more control over widgets and help text, but it basically stores the data twice.

#### Custom widget for the metatag field

Metatag module exposes an extra field on the form display and a different widget
can be chosen.  If we write our own widget, then we can simply extend the
existing class, and provide our own (simplified) UI.

## Decision

Custom widget for the metatag field.

We'll start with just "page title" and "description".  In the future we could
add an image widget that controls all 4+ image-related metatags.

## Consequences

- total control
- no cognitive overload (for editors or engineers)
- no duplication of data
- ability to scale in the future
