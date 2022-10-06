#!/bin/bash

# Run scripts via lando if available
if [ -e .env ]; then
  cd docroot/themes/humsci/humsci_basic
  lando npm ci
  lando npm run visreg
else
  echo "Add a .env to the root of this project that contains you Percy Token. See project README."
fi
