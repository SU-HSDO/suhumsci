#!/bin/bash

set -e

echo "=== Installing dependencies ==="

# nvm is installed by the node devcontainer feature (devcontainer.json), in a
# shared, group-writable location, and already installs and aliases the
# version pinned there as default, regardless of which user attaches. Source
# it here, and re-run `nvm install` (reads .nvmrc) in case the feature's
# pinned version and .nvmrc have drifted out of sync since.
export NVM_DIR="/usr/local/share/nvm"
if [ -s "$NVM_DIR/nvm.sh" ]; then
  . "$NVM_DIR/nvm.sh"
fi
nvm install
nvm use

# Install Composer dependencies
composer install -n

# Build theme CSS/JS. Not committed to the repo, so this must run explicitly.
composer build-theme

# Copy Codespaces drush configuration
cp .devcontainer/drush.yml drush/local.drush.yml

# Generate multisite settings
drush sws:multisite:settings

# Update drush URI to the Codespaces URL. drush/drush.yml declares this file
# as a merged config source for every drush invocation from this repo.
sed -i "s|uri:.*$|uri: https://$CODESPACE_NAME-80.app.github.dev|" docroot/sites/default/local.drush.yml

# Install default site only
echo "=== Installing default site ==="
drush sws:multisite:install -n --site=default

echo "=== Configuring Apache for Codespaces ==="

# Configure Apache for Codespaces port forwarding
sed -i 's/Listen 80$//' /etc/apache2/ports.conf
sed -i 's/<VirtualHost \*:80>/ServerName 127.0.0.1\n<VirtualHost \*:8080>/' /etc/apache2/sites-enabled/000-default.conf

echo "=== Setting up web root ==="

# Create symlink to repo root as Apache document root
rm -rf /var/www/html
ln -s /workspaces/suhumsci /var/www/html

# Set file permissions for Drupal files directory
chown -R www-data:www-data docroot/sites/default/files
chmod -R 755 docroot/sites/default/files

# Rebuild cache so aggregated CSS/JS is regenerated against the final settings
drush @default.local cr

echo "=== Installation complete ==="
