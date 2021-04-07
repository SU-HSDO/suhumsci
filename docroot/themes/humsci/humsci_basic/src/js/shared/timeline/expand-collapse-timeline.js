// Timelines are expanded by default
// Find when a timeline has been set to collapsed so that we can
// adjust the default attribute values
const timelineCollapsed = document.querySelectorAll('.hb-timeline__collapsed');

// Find timeline items are are open inside of timelineCollapsed and close them!
timelineCollapsed.forEach(timeline => {
  let items;
  let summaries;
  
  // Find all the timeline items inside of the collapsed timeline
  items = timeline.querySelectorAll('.hb-timeline-item');

  // Remove open attribute from these items
  items.forEach(item => {
    item.removeAttribute('open');
  });

  // Find the summary element and update the aria attribute values
  summaries = timeline.querySelectorAll('.hb-timeline-item__summary');

  summaries.forEach(summary => {
    summary.setAttribute('aria-expanded', 'false');
    summary.setAttribute('aria-pressed', 'false');
  });
});
