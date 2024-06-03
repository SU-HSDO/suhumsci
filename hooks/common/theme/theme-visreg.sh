#!/bin/bash

cd docroot/themes/humsci/humsci_basic
# Percy Visual Regression Testing requires a .env file with the Percy Token in the theme folder.
if [ -e .env ]; then
  lando npm ci
  lando npm run visreg
else
  echo "Add a .env file with your Percy Token in the theme root folder. See docroot/themes/humsci/humsci_basic/README.md for more information."
fi
