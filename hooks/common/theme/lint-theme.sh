#!/bin/bash

# Run scripts via lando if available
if [ -e .lando.yml ]  && command -v lando &> /dev/null
then
  cd docroot/themes/humsci/humsci_basic
  lando npm ci
  lando npm run test
else
  cd docroot/themes/humsci/humsci_basic
  npm ci
  npm run lint
fi
