#!/bin/bash
# Sync a multisite database from Acquia.
# Usage: lando sync <site_name>

if [ -z "$1" ]; then
  echo "Usage: lando sync <site_name>"
  echo "Example: lando sync archaeology"
  echo ""
  echo "Run 'lando sites' to see available multisites."
  exit 1
fi

/app/vendor/bin/blt drupal:sync --site="$1"
