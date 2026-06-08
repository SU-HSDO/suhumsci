#!/bin/bash

set -e

echo "=== Installing dependencies ==="

# Install Composer dependencies
composer install -n

# Generate multisite settings
drush sws:multisite:settings

# Install default site only
echo "=== Installing default site ==="
drush sws:multisite:install -n --site=default

echo "=== Installation complete ==="
