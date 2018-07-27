(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.calendar = {
    attach: function (context, settings) {
      $('.calendar-pattern', context).once('applied-calendar').each(function () {
        var events = [];
        $(this).siblings('.calendar-items').find('.event').each(function () {

          var title = $(this).find('a').detach();
          events.push({
            title: title.text(),
            start: $(this).text(),
            url: title.attr('href')
          });
        });
        console.log(events);
        $(this).fullCalendar({
          events: events
        })
      })
    }
  };
})(jQuery, Drupal);
