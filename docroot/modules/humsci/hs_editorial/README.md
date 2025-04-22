# Stanford School of Humanities And Sciences Editorial Tools

This module provides tools for managing a straightforward and lightweight editorial workflow. It’s designed for teams that need basic editorial features without the complexity of full-scale Drupal content moderation modules, which are powerful but can be overwhelming due to their extensive configuration needs and changes to admin interfaces.

## Features

Preparer Role: This role is tailored for team members who handle initial content creation and edits but don’t manage final publishing. These abilities are granted based on the user role, which does not align with the typical cascading relationship of Drupal's roles and permissions. Some of these programmatic changes include:

- Creating Content: All new content created by Preparers is automatically set to be unpublished (contrary to the default node behavior), ensuring it doesn’t go live without review.
- Editing Drafts: Preparers can create and modify unpublished content. They can't edit content once it's published.
- Menu Management: Preparers can add new, unpublished content to menus. However, their access is limited to only this aspect of menu management, and they won’t be able to alter menu settings or manage published content within menus.
