#!/bin/bash

set -e

ADMIN_LOGIN=$(drush @default.local uli)

echo ""
echo "=== Setup complete ==="
echo ""
echo "Site URL: https://${CODESPACE_NAME}-80.app.github.dev"
echo ""
echo "Admin login link:"
echo "$ADMIN_LOGIN"
echo ""
echo "Next: See .devcontainer/QUICKSTART.md for workflow instructions"
echo ""
