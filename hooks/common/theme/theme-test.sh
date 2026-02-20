#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"
NPM_CMD=$("$DIR/theme-get-command.sh")

cd docroot/themes/humsci/humsci_basic || exit

echo "Running theme tests..."
${NPM_CMD} run test
