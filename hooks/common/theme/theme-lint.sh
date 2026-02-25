#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"
NPM_CMD=$("$DIR/theme-get-command.sh")

cd docroot/themes/humsci/humsci_basic || exit

echo "Running linters on the theme..."
${NPM_CMD} run lint
