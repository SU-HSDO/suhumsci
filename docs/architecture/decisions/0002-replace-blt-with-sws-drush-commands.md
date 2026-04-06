# 2. Replace Acquia BLT with SWS Drush Commands

## Status

Accepted

## Context
The `acquia/blt` (https://github.com/acquia/blt) tool we use in our projects has been deprecated for over a year now (since January 2025). While it still functions, we have an adequate, in-house replacement in `sws-drush-commands` (https://github.com/SU-SWS/sws-drush-commands). Additionally, BLT does not support Drupal 11 and prevents the upgrade process.

## Decision
- Replace `acquia/blt` with `sws-drush-commands` (SWSDC).
- If there is a functional equivalent for any command in [Acquia CLI (ACLI)](https://docs.acquia.com/acquia-cloud-platform/add-ons/acquia-cli/overview), use the ACLI command instead of a custom drush command.

## Consequences
All BLT commands, references, and usage will need to be replaced with appropriate Drush commands or other available tools.
