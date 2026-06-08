#!/bin/bash

set -e

echo "=== Initializing Codespaces environment ==="

# Start MySQL
echo "Starting MySQL service..."
service mysql start
sleep 3

# Create database
echo "Creating database..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS drupal;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'drupal'@'localhost' IDENTIFIED BY 'drupal';"
mysql -u root -e "GRANT ALL PRIVILEGES ON drupal.* TO 'drupal'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Install Composer dependencies
echo "Installing Composer dependencies..."
cd /workspace
composer install -n

# Generate encryption key if it doesn't exist
KEYS_DIR="/workspaces/.codespace-keys"
ENCRYPTION_FILE="$KEYS_DIR/encryption.key"

if [ ! -d "$KEYS_DIR" ]; then
  mkdir -p "$KEYS_DIR"
fi

if [ ! -f "$ENCRYPTION_FILE" ]; then
  echo "Generating encryption key..."
  php -r "
    \$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    \$randomString = '';
    for (\$i = 0; \$i < 256 / 8; \$i++) {
      \$randomString .= \$characters[rand(0, strlen(\$characters) - 1)];
    }
    file_put_contents('$ENCRYPTION_FILE', \$randomString);
  "
  echo "Encryption key stored in $ENCRYPTION_FILE"
fi

# Load encryption key into environment
export REAL_AES_ENCRYPTION=$(cat "$ENCRYPTION_FILE")

# Generate multisite settings
echo "Generating multisite settings..."
drush sws:multisite:settings

# Install default site only
echo "Installing default site..."
drush sws:multisite:install -n --site=default

# Generate admin login link
ADMIN_LOGIN=$(drush @default.local uli)

echo ""
echo "=== Setup complete ==="
echo ""
echo "Site URL: https://${CODESPACE_NAME}-80.app.github.dev"
echo ""
echo "Admin login link:"
echo "$ADMIN_LOGIN"
echo ""
echo "Next: See .devcontainer/QUICKSTART.md for workflow instructions"
echo ""
