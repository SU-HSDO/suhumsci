// Timelines are expanded by default
// Find when a timeline has been set to collapsed so that we can
// adjust the default attribute values
(function (Drupal, once) {
  Drupal.behaviors.timelineCollapseBehavior = {
    attach(context) {
      const timelineCollapsed = once(
        'collapsed-timeline',
        '.hb-timeline__collapsed',
        context,
      );

      // Find timeline items are are open inside of timelineCollapsed and close them!
      timelineCollapsed.forEach((timeline) => {
        // Find all the timeline items inside of the collapsed timeline
        const items = timeline.querySelectorAll('.hb-timeline-item');

        // Remove open attribute from these items
        items.forEach((item) => {
          item.removeAttribute('open');
        });
      });

      // When a user clicks on a timeline, set open property accordingly
      const timelineItems = once('timeline-item', '.hb-timeline-item', context);

      const searchQuery = new URLSearchParams(window.location.search);
      const params = Object.fromEntries(searchQuery.entries());

      function toggleTimelineFromSearch() {
        const searchTerm = params.search.toLowerCase();

        timelineItems.forEach((timeline) => {
          if (timeline.textContent.toLowerCase().includes(searchTerm)) {
            timeline.setAttribute('open', '');
          }
        });
      }

      if (
        Object.keys(params).length
        && Object.prototype.hasOwnProperty.call(params, 'search')
      ) {
        toggleTimelineFromSearch();
      }
    },
  };
}(Drupal, once));
