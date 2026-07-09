#!/bin/bash

set -e

code docs/CodespacesWorkflow.md

ADMIN_LOGIN=$(drush @default.local uli --no-browser --uri="https://${CODESPACE_NAME}-80.app.github.dev")

echo ""
echo "=== Setup complete ==="
echo ""
echo "Site URL: https://${CODESPACE_NAME}-80.app.github.dev"
echo ""
echo "Admin login link:"
echo "$ADMIN_LOGIN"
echo ""
echo "Next: See docs/CodespacesWorkflow.md for workflow instructions"
echo ""

# Open the login link in the client browser automatically, when available
if [ -n "$BROWSER" ]; then
  "$BROWSER" "$ADMIN_LOGIN" 2>/dev/null || true
fi
