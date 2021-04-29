/**
 * @file
 * Stops page from changing when user is posting.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.humsciParagraphBehaviors = {
    attach: function (context, settings) {
      const tabs = $('ul.paragraphs-tabs', context);
      tabs.removeClass('paragraphs-tabs')
        .removeClass('primary')
        .addClass('hs-paragraphs-tabs');
      const dragColumn = tabs.parent().find('.paragraphs-subform').closest('tr').find('td.field-multiple-drag');
      if (dragColumn.length > 0) {
        dragColumn.append(tabs.detach());
      }
    }
  };
})(jQuery, Drupal);
