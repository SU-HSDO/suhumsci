#!/bin/bash
# Lists all available multisites from the sites directory.

sites=()
for settings in /app/docroot/sites/*/settings.php; do
  site_dir=$(basename "$(dirname "$settings")")
  if [ "$site_dir" != "default" ]; then
    sites+=("$site_dir")
  fi
done

IFS=$'\n' sorted=($(sort <<<"${sites[*]}")); unset IFS

echo "Available multisites (${#sorted[@]}):"
for site in "${sorted[@]}"; do
  echo "  $site"
done
