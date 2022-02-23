// Timelines are expanded by default
// Find when a timeline has been set to collapsed so that we can
// adjust the default attribute values
const timelineCollapsed = document.querySelectorAll('.hb-timeline__collapsed');

// Find timeline items are are open inside of timelineCollapsed and close them!
timelineCollapsed.forEach((timeline) => {
  // let items;
  // let summaries;

  // Find all the timeline items inside of the collapsed timeline
  const items = timeline.querySelectorAll('.hb-timeline-item');

  // Remove open attribute from these items
  items.forEach((item) => {
    item.removeAttribute('open');
  });

  // Find the summary element and update the aria attribute values
  const summaries = timeline.querySelectorAll('.hb-timeline-item__summary');

  summaries.forEach((summary) => {
    summary.setAttribute('aria-expanded', 'false');
    summary.setAttribute('aria-pressed', 'false');
  });
});

// When a user clicks on a timeline, update the aria properties accordingly
const timelineItems = document.querySelectorAll('.hb-timeline-item');

if (timelineItems) {
  timelineItems.forEach((timelineItem) => {
    const summary = timelineItem.querySelector('.hb-timeline-item__summary');

    // Find the value of aria-expanded for a timeline item summary
    let ariaExpanded = summary.getAttribute('aria-expanded');

    // Update aria values!
    summary.addEventListener(('keypress', 'click'), () => {
      if (ariaExpanded === 'true') {
        summary.setAttribute('aria-expanded', 'false');
        summary.setAttribute('aria-pressed', 'false');
      } else {
        summary.setAttribute('aria-expanded', 'true');
        summary.setAttribute('aria-pressed', 'true');
      }

      // Retain updated value for the aria-expanded attribute
      ariaExpanded = summary.getAttribute('aria-expanded');
    });
  });
}
