#!/bin/bash

NPM_CMD="npm"
if [ -n "$DDEV_PROJECT" ] || [ -n "$LANDO_PROJECT" ]; then
  # If inside a container, use npm directly.
  NPM_CMD="npm"
elif [ -e .ddev/config.yaml ] && command -v ddev &> /dev/null; then
  # If outside a DDEV container, use ddev npm.
  NPM_CMD="ddev npm"
elif [ -e .lando.yml ]  && command -v lando &> /dev/null; then
  # If outside a Lando container, use lando npm.
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
