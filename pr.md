# Summary
Changes the Content Staging workflow from a weekly to a biweekly schedule, running only on prod release weeks. A new `check-schedule` gate job calculates whether the current week is a prod release week using a repository variable (`PROD_WEEK_REFERENCE_MONDAY`) and skips the database copy on off weeks.

## Backstory
The H&S web team requested that staging environments be kept around longer before being wiped and refreshed from production. Previously the copy ran every week; this change reduces the cadence to every other week, aligned to the start of each prod release cycle.

The biweekly gate uses a 14-day cycle anchored to a repository variable set to the Monday of a known prod release week. Days 0-6 in the cycle are prod release weeks and the copy runs; days 7-13 are off weeks and the copy is skipped. The `workflow_dispatch` trigger always bypasses the check, allowing a manual run at any time. If the repository variable is not set, the workflow falls back to running every week.

A helper script (`check-prod-week.sh`) is included in the repository root to verify date calculations before updating the variable.

<!-- No JIRA ticket number provided — add if applicable:
[HSD8-NNNN](https://stanfordits.atlassian.net/browse/HSD8-NNNN)
-->

## Need Review By (Date)
_[TBD — please fill in]_

## Urgency
_[TBD — please fill in]_

## Steps to Test
1. Set `PROD_WEEK_REFERENCE_MONDAY` to this week's Monday (e.g., `2026-06-15`) in Settings > Secrets and variables > Actions > Variables.
1. Run `./check-prod-week.sh 2026-06-15` and confirm today is shown as a prod release week.
1. Run `./check-prod-week.sh 2026-06-15 <NEXT_OFF_TUESDAY>` (the Tuesday of the following week) and confirm it shows as an off week.
1. Trigger the Content Staging workflow manually via `workflow_dispatch` and confirm both `check-schedule` and `copy-prod-to-stage` jobs complete.
1. In the `check-schedule` job logs, verify the day-in-cycle value and `should_run` output are correct.
1. Set `PROD_WEEK_REFERENCE_MONDAY` to a Monday from last week (placing today in the off week) and trigger again via `workflow_dispatch` — `copy-prod-to-stage` should still run since `workflow_dispatch` bypasses the biweekly check, but `check-schedule` logs should show `should_run=false`.
