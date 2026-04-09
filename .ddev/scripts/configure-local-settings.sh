#!/bin/bash

# Configure local.settings.php files for DDEV environment
# This script updates database connection settings in all local.settings.php files

echo "Configuring local.settings.php files for DDEV environment..."

# Update database username from 'root' to 'db'
echo "Updating database username..."
find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'username' => 'user'/'username' => 'db'/g" {}

# Update database password from 'password' to 'db'
echo "Updating database password..."
find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'password' => 'password'/'password' => 'db'/g" {}

# Update database host from 'localhost' to 'db'
echo "Updating database host..."
find /var/www/html/docroot/sites/ -name local.settings.php | xargs -I {} sed -i "s/'host' => 'localhost'/'host' => 'db'/g" {}

# Grant database privileges to 'db' user
echo "Granting database privileges to 'db' user..."
mysql -u root -proot -e "GRANT ALL PRIVILEGES ON *.* TO 'db'@'%' WITH GRANT OPTION;"

echo "Local settings configuration complete!"
