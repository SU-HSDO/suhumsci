#!/bin/bash

set -e

echo "=== Installing dependencies ==="

# Install nvm and source it in this script. theme-get-command.sh has its own
# nvm detection, but relying on it alone was not reliable, so install and
# activate the .nvmrc version here too, before anything else needs node.
# install.sh's final step chmods nvm-exec, which is not something we use
# directly (we only source nvm.sh) and can fail on its own in this container.
# Do not let that be fatal here; instead verify nvm.sh itself was produced.
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.5/install.sh | bash || true
export NVM_DIR="$HOME/.nvm"
if [ ! -s "$NVM_DIR/nvm.sh" ]; then
  echo "nvm installation failed: $NVM_DIR/nvm.sh not found" >&2
  exit 1
fi
. "$NVM_DIR/nvm.sh"
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
