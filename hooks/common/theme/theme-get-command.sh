#!/bin/bash

if [ -n "$DDEV_PROJECT" ] || [ -n "$LANDO_PROJECT" ]; then
  # If inside a container, use npm directly.
  echo "npm"
elif [ -d ".ddev/php" ] && command -v ddev &> /dev/null; then
  # If outside a DDEV container, use ddev npm.
  echo "ddev npm"
elif [ -e .lando.yml ]  && command -v lando &> /dev/null; then
  # If outside a Lando container, use lando npm.
  echo "lando npm"
else
  echo "npm"
fi

cd docroot/themes/humsci/humsci_basic || exit

if [ -d "$HOME/.nvm" ] && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" && command -v nvm &> /dev/null; then
  echo "Installing correct version of node..." >&2
  nvm install >&2
fi

if [ ! -d node_modules ]; then
  echo "Installing npm dependencies..." >&2
  ${NPM_CMD} ci
fi
