# Drupal Component Configuration Defaults

When you are creating a new component or adding new fields/functionality to existing components there is default configuration of the fields and displays that we need to utilize.

## Form Display Settings

These settings can be found on the "Manage form display" tab on most Entity types in Drupal; Paragraph components, Node Content Types, Taxonomy Terms, etc: `/admin/structure/paragraphs_type/[entity_type_name]/form-display`.

- ### Link Field

  - **Widget:** Linkit
  - **Widget Settings:**
    - Use the "Default" option for Linkit profile, if not already set.
    - Set the character limit to 80.

- ### Paragraph Field {#form-display-paragraph-field}

  - **Widget:** Paragraphs EXPERIMENTAL
  - **Widget Settings:**
    - Edit Mode: Closed
    - Autocollapse: All

## Display Settings

- ### Image Field

  - **Formatter:**
    - This should be a Media image field, use "Media Responsive Image Style" for your formatter as a general rule to start.
  - **Widget Settings:**
    - View Mode: Caption/Credit
      - There may be exceptions to this setting, if we do not want this image to have the caption/credit overlay style, this should not be selected. New image fields that will need the caption/credit overlay should have this added to the Acceptance Criteria for the work as good practice for review and testing.

## General Field Settings and Best Practices

- ### Paragraph Fields

  - Adding a new Paragraphs component to the Flexible Page "Components" field?
    - New components should be added in alphabetical order AFTER Text Area.
  - Adding a new Paragraphs component to the "Collection" components list?
    - Components should be in alphabetical order.
  - [Paragraph Form Display Settings.](#paragraph-field-form-display)

- ### Character Limit for Fields

  - Update the character limit in the Count down message for character limit where applicable when creating new fields that should have a limited size. This limit should be added to Acceptance Criteria for work that requires it.

- ### List item fields applying CSS

  - These fields should be set to "Required field" to remove the `- none -` option from the list and have a "Default Value" selection selected in the field settings.
