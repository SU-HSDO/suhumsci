#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"
. "$DIR/theme-get-command.sh"

cd "$DIR"/../../../docroot/themes/humsci/humsci_basic || exit

# Percy Visual Regression Testing requires a .env file with the Percy Token in the theme folder.
if [ -e .env ]; then
  ${NPM_CMD} ci
  ${NPM_CMD} run visreg
else
  echo "Add a .env file with your Percy Token in the theme root folder. See docroot/themes/humsci/humsci_basic/README.md for more information."
fi
