#!/bin/bash

NPM_CMD="npm"
# Run scripts via ddev or lando if available
if [ -e .ddev/config.yaml ] && command -v ddev &> /dev/null; then
  NPM_CMD="ddev npm"
elif [ -e .lando.yml ]  && command -v lando &> /dev/null; then
  NPM_CMD="lando npm"
fi

cd docroot/themes/humsci/humsci_basic
# Percy Visual Regression Testing requires a .env file with the Percy Token in the theme folder.
if [ -e .env ]; then
  ${NPM_CMD} ci
  ${NPM_CMD} run visreg
else
  echo "Add a .env file with your Percy Token in the theme root folder. See docroot/themes/humsci/humsci_basic/README.md for more information."
fi
