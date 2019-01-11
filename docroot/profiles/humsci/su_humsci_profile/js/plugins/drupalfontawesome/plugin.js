/**
 * This entire file is a copy of the font awesome module file. It allows the
 * icon to live in the profile instead of in the module for customization.
**/

(function ($, Drupal, drupalSettings, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('drupalfontawesome', {
    icons: 'drupalfontawesome',
    hidpi: true,

    init: function init(editor) {
      editor.addCommand('drupalfontawesome', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec: function exec(execEditor) {
          var saveCallback = function saveCallback(returnValues) {
            execEditor.fire('saveSnapshot');

            var selection = execEditor.getSelection();
            var range = selection.getRanges(1)[0];

            var container = new CKEDITOR.dom.element('span', execEditor.document);
            container.addClass('fontawesome-icon-inline');
            var icon = new CKEDITOR.dom.element(returnValues.tag, execEditor.document);
            icon.setAttributes(returnValues.attributes);
            container.append(icon);
            container.appendHtml('&nbsp;');

            range.insertNode(container);
            range.select();

            execEditor.fire('saveSnapshot');

            execEditor.fire('insertedIcon');
          };

          var dialogSettings = {
            title: execEditor.config.drupalFontAwesome_dialogTitleAdd,
            dialogClass: 'fontawesome-icon-dialog'
          };

          Drupal.ckeditor.openDialog(execEditor, Drupal.url('fontawesome/dialog/icon/' + execEditor.config.drupal.format), {}, saveCallback, dialogSettings);
        }
      });

      if (editor.ui.addButton) {
        editor.ui.addButton('DrupalFontAwesome', {
          label: Drupal.t('Font Awesome'),
          command: 'drupalfontawesome'
        });
      }
    }
  });

  $.each(drupalSettings.editor.fontawesome.allowedEmptyTags, function (_, tag) {
    CKEDITOR.dtd.$removeEmpty[tag] = 0;
  });

  Drupal.FontAwesome = {};

  Drupal.FontAwesome.tagsToSvg = function (drupalSettings, thisEditor) {
    $.each(drupalSettings.editor.fontawesome.fontawesomeLibraries, function (_, library) {
      var $script = document.createElement('script');
      var $editorInstance = CKEDITOR.instances[thisEditor.editor.name];

      $script.src = library;

      $editorInstance.document.getHead().$.appendChild($script);
    });
  };

  Drupal.FontAwesome.svgToTags = function (thisEditor) {
    var htmlBody = thisEditor.editor.getData();

    htmlBody = htmlBody.replace(/<svg .*?class="svg-inline--fa.*?<\/svg><!--\s?(.*?)\s?-->/g, '$1');

    thisEditor.editor.setData(htmlBody);
  };

  CKEDITOR.on('instanceReady', function (ev) {
    Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);

    ev.editor.on('mode', function () {
      if (ev.editor.mode === 'source') {
        Drupal.FontAwesome.svgToTags(ev);
      } else if (ev.editor.mode === 'wysiwyg') {
        Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);
      }
    });

    ev.editor.on('insertedIcon', function () {
      ev.editor.setData(ev.editor.getData());

      Drupal.FontAwesome.tagsToSvg(drupalSettings, ev);
    });
  });
})(jQuery, Drupal, drupalSettings, CKEDITOR);
