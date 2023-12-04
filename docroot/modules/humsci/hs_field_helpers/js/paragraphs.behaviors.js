/**
 * @file
 * Stops page from changing when user is posting.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.humsciParagraphBehaviors = {
    attach: function (context, settings) {
      $('ul.paragraphs-tabs', context).remove();
      $(once('hs-behaviors', '.paragraphs-subform', context)).each(function () {
        const $subform = $(this);
        const $behaviors = $(this).siblings('.paragraphs-behavior')
        const $tabs = $('<ul class="hs-paragraphs-tabs tabs">');
        const contentLink = $('<a href="#">').text('Content').click(function(e){
          e.preventDefault();
          $subform.show();
          $behaviors.hide();
        });
        const behaviorsLink = $('<a href="#">').text('Style').click(function(e){
          e.preventDefault();
          $subform.hide();
          $behaviors.show();
        });
        $tabs.append($('<li>').append(contentLink));
        $tabs.append($('<li>').append(behaviorsLink));

        if ($behaviors.find('input, select, textarea, button').length > 0) {
          $subform.closest('tr').find('td.field-multiple-drag').append($tabs);
        }
      });
    }
  };
})(jQuery, Drupal, once);
