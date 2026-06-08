#!/bin/bash

set -e

echo "=== Configuring Apache for Codespaces ==="

# Configure Apache for Codespaces port forwarding
sed -i 's/Listen 80$//' /etc/apache2/ports.conf
sed -i 's/<VirtualHost \*:80>/ServerName 127.0.0.1\n<VirtualHost \*:8080>/' /etc/apache2/sites-enabled/000-default.conf

echo "=== Setting up web root ==="

# Create symlink to repo root as Apache document root
rm -rf /var/www/html
ln -s /workspaces/suhumsci /var/www/html

echo "=== Installing dependencies ==="

# Install Composer dependencies
composer install -n

# Copy Codespaces drush configuration
cp .devcontainer/drush.yml drush/local.drush.yml

# Generate multisite settings
drush sws:multisite:settings

# Update drush URI to the Codespaces URL
sed -i "s|uri:.*$|uri: https://$CODESPACE_NAME-80.app.github.dev|" docroot/sites/default/local.drush.yml

# Generate encryption key for REAL_AES_ENCRYPTION
if [ -z "$REAL_AES_ENCRYPTION" ]; then
  export REAL_AES_ENCRYPTION=$(head -c 32 /dev/urandom | base64)
fi

# Install default site only
echo "=== Installing default site ==="
drush sws:multisite:install -n --site=default

# Set file permissions for Drupal files directory
chown -R www-data:www-data docroot/sites/default/files
chmod -R 755 docroot/sites/default/files

echo "=== Installation complete ==="
