#!/usr/bin/env bash
# Usage: ./check-prod-week.sh <REFERENCE_MONDAY> [CHECK_DATE]
#   REFERENCE_MONDAY — the Monday of a known prod release week (YYYY-MM-DD)
#   CHECK_DATE       — date to test (YYYY-MM-DD); defaults to today
#
# Days 0–6 after the reference Monday (and every 14 days thereafter) = prod week.
# Days 7–13 = off week.
#
# Examples:
#   ./check-prod-week.sh 2026-06-15
#   ./check-prod-week.sh 2026-06-15 2026-06-30
#   ./check-prod-week.sh 2026-06-15 2026-07-07

REFERENCE_MONDAY="${1:-}"
CHECK_DATE="${2:-$(date +%Y-%m-%d)}"

if [ -z "$REFERENCE_MONDAY" ]; then
  echo "Usage: $0 <REFERENCE_MONDAY> [CHECK_DATE]"
  echo "  REFERENCE_MONDAY: the Monday of a known prod release week (YYYY-MM-DD)"
  echo "  CHECK_DATE:       date to test (YYYY-MM-DD), defaults to today"
  exit 1
fi

REF_EPOCH=$(date -d "$REFERENCE_MONDAY" +%s)
CHECK_EPOCH=$(date -d "$CHECK_DATE" +%s)
DAYS_SINCE=$(( (CHECK_EPOCH - REF_EPOCH) / 86400 ))
DAY_IN_CYCLE=$(( DAYS_SINCE % 14 ))

echo "Reference Monday : $REFERENCE_MONDAY"
echo "Check date       : $CHECK_DATE"
echo "Days since ref   : $DAYS_SINCE"
echo "Day in cycle     : $DAY_IN_CYCLE (0–6 = prod week, 7–13 = off week)"

if [ "$DAY_IN_CYCLE" -lt 7 ]; then
  echo "Result           : PROD RELEASE WEEK — content-stage would run"
else
  echo "Result           : OFF WEEK — content-stage would skip"
fi
