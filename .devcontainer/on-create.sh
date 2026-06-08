#!/bin/bash

set -e

echo "=== Installing dependencies ==="

# Install Composer dependencies
composer install -n

# Generate multisite settings
drush sws:multisite:settings

# Generate encryption key for REAL_AES_ENCRYPTION
if [ -z "$REAL_AES_ENCRYPTION" ]; then
  export REAL_AES_ENCRYPTION=$(head -c 32 /dev/urandom | base64)
fi

# Install default site only
echo "=== Installing default site ==="
drush sws:multisite:install -n --site=default

echo "=== Installation complete ==="
