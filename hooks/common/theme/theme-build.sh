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
if [ ! -d node_modules ]; then
  echo "Installing npm dependencies..."
  ${NPM_CMD} ci
fi
echo "Building theme..."
${NPM_CMD} run build
