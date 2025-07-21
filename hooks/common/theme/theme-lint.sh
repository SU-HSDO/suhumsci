#!/bin/bash

NPM_CMD="npm"
# Run scripts via ddev if available
if [ -e .ddev/config.yaml ] && command -v ddev &> /dev/null; then
  NPM_CMD="ddev npm"
fi

cd docroot/themes/humsci/humsci_basic
if [ ! -d node_modules ]; then
  echo "Installing npm dependencies..."
  ${NPM_CMD} ci
fi
echo "Running linters on the theme..."
${NPM_CMD} run lint
