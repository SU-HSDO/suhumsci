#!/bin/bash

NPM_CMD="npm"
# Run scripts via lando if available
if [ -e .lando.yml ] && command -v lando &> /dev/null; then
  NPM_CMD="lando npm"
fi

cd docroot/themes/humsci/humsci_basic
if [ ! -d node_modules ]; then
  echo "Installing npm dependencies..."
  ${NPM_CMD} ci
fi
echo "Building theme..."
${NPM_CMD} run build
