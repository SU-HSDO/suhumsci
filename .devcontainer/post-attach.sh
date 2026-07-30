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

# Open the plain site URL automatically, when available. Not the login link
# itself: on this site that redirects through SSO (PingFederate) and lands on
# an ugly, unusable URL. The real login link is printed above to copy/paste.
if [ -n "$BROWSER" ]; then
  "$BROWSER" "https://${CODESPACE_NAME}-80.app.github.dev" 2>/dev/null || true
fi
