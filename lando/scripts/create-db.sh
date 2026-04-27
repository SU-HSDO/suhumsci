#!/bin/bash
# Creates a database for a multisite.
# Usage: lando create-db <site_name>

if [ -z "$1" ]; then
  echo "Usage: lando create-db <site_name>"
  echo "Example: lando create-db archaeology"
  exit 1
fi

if [[ ! "$1" =~ ^[a-zA-Z0-9_]+$ ]]; then
  echo "Error: Site name must contain only letters, numbers, and underscores."
  exit 1
fi

DB_NAME="suhumsci_$1"
mysql -udrupal -pdrupal -hdatabase -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;" 2>/dev/null
echo "Database $DB_NAME created (or already exists)."
