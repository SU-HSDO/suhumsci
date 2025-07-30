# H&S Events Module

This module provides functionality for managing events in the Humanities & Sciences site.

## Auto-Unpublish Functionality

The module includes an automated cron job that unpublishes past events based on specific criteria.

### How it works

The cron job runs automatically and checks for events that meet the following criteria:

1. **Content Type**: `hs_event`
2. **Status**: Published (status = 1)
3. **Auto-unpublish field**: Set to `true` (field_auto_unpublish = 1)
4. **Event Date**: The event's end date is in the past

### Date Field Checked

The system checks the main event date field:

- **field_hs_event_date**: The event date range

An event will be unpublished if the event date's end time is in the past.

### Manual Testing

You can manually test the auto-unpublish functionality using Drush:

```bash
drush hs-events-auto-unpublish
```

or

```bash
drush hs-events-unpublish
```

### Logging

The system logs all auto-unpublish actions to the Drupal logs:

- **Notice level**: Individual event unpublish actions with event title and ID
- **Info level**: Summary of how many events were unpublished during the cron run

You can view these logs in the Drupal admin interface under Reports > Recent log messages, or using Drush:

```bash
drush watchdog-show --type=hs_events
```

### Configuration

To enable auto-unpublish for an event:

1. Edit the event node
2. In the "Options" section, check the "Auto-unpublish when event is past" checkbox
3. Save the event

### Bulk Operations

The module provides a bulk operation to enable auto-unpublish for multiple events at once:

1. Go to Admin > Content > Manage Content > Events
2. Select one or more events using the checkboxes
3. Choose "Auto-unpublish once event is past" from the Actions dropdown
4. Apply the action

This bulk operation is available in all event management views.

### Cron Setup

The auto-unpublish functionality runs automatically when Drupal's cron system executes. Ensure that your site's cron is properly configured to run regularly (typically every hour or daily).

You can manually run cron using:

```bash
drush cron
```
