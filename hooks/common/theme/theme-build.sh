#!/bin/bash

DIR="$(dirname "$(realpath "$0")")"
. "$DIR/theme-get-command.sh"

cd "$DIR"/../../../docroot/themes/humsci/humsci_basic || exit

echo "Building theme..." >&2
${NPM_CMD} run build
