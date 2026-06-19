#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"
. "$DIR/theme-get-command.sh"

cd "$DIR"/../../../docroot/themes/humsci/humsci_basic || exit

echo "Watching for changes in the theme..."
${NPM_CMD} start
