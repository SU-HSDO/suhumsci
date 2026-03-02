#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"

echo "HSDP_COMPILE_ENVIRONMENT: $HSDP_COMPILE_ENVIRONMENT" >&2
if [ -n "$DDEV_PROJECT" ] || [ -n "$LANDO_PROJECT" ]; then
  echo "Compiling the theme using the npm within ddev/lando..." >&2
  NPM_CMD="npm"
elif [ -d "$DIR/../../../.ddev/php" ] && [ "$HSDP_COMPILE_ENVIRONMENT" == "ddev" ] && command -v ddev &> /dev/null; then
  echo "Compiling the theme by calling 'ddev npm'..." >&2
  NPM_CMD="ddev npm"
elif [ -e .lando.yml ]  && [ "$HSDP_COMPILE_ENVIRONMENT" == "lando" ] && command -v lando &> /dev/null; then
  echo "Compiling the theme by calling 'lando npm'..." >&2
  NPM_CMD="lando npm"
else
  echo "Compiling the theme by using npm on bare metal..." >&2
  NPM_CMD="npm"
fi

cd "$DIR"/../../../docroot/themes/humsci/humsci_basic || exit 1

if [ -d "$HOME/.nvm" ] && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" && command -v nvm &> /dev/null; then
  echo "Installing correct version of node..." >&2
  nvm install >&2
fi

if [ ! -d node_modules ]; then
  echo "Installing npm dependencies..." >&2
  ${NPM_CMD} ci
  if ! ${NPM_CMD} ci; then
    echo
    echo "Installing NPM_CMD dependencies failed.  Some failures may be due to where you are compiling the code.
    See the root README.md about alternate ways of compiling the theme in/out of ddev/lando."  >&2
    exit 1
  fi
fi

# Callers source this file, so export NPM_CMD for use in the parent shell.
export NPM_CMD
