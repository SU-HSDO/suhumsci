#!/bin/bash

# Create site-specific databases for multisite
# This script creates a separate database for each site directory found in docroot/sites/

echo "Creating site-specific databases for multisite..."

for site_dir in /var/www/html/docroot/sites/*/; do
  if [ -d "$site_dir" ]; then
    site_name=$(basename "$site_dir")
    if [ "$site_name" != "default" ] && [ "$site_name" != "all" ] && [ "$site_name" != "example.sites.php" ]; then
      echo "Creating database for site: $site_name"
      mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS suhumsci_$site_name;"
    fi
  fi
done

echo "Database creation complete!"
