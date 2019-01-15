(function ($, Drupal, drupalSettings, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('humscifontawesome', {icons: 'humscifontawesome'});

  CKEDITOR.on('instanceReady', function (ev) {
    $('.cke_button__drupalfontawesome .cke_button_icon', ev.editor.container.$).css('background-image', 'url("' + ev.editor.config.humsciFontAwesome + '")');
  });
})(jQuery, Drupal, drupalSettings, CKEDITOR);
