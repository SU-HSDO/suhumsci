#!/bin/bash

# Run scripts via lando if available
if [ -e .lando.yml ]; then
  cd docroot/themes/humsci/humsci_basic
  lando npm ci
  lando npm run watch
else
  cd docroot/themes/humsci/humsci_basic
  npm ci
  npm run watch
fi
