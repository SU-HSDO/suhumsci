import { Plugin } from 'ckeditor5/src/core';
import { View } from 'ckeditor5/src/ui';

export default class BookmarkHelp extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'BookmarkHelp';
  }

  /**
   * @inheritDoc
   */
  static get requires() {
    return ['Bookmark', 'BookmarkUI', 'BookmarkEditing', 'ContextualBalloon'];
  }

  init() {
    const { editor } = this;
    const balloon = editor.plugins.get('ContextualBalloon');

    balloon.on('change:visibleView', () => {
      const bookmarkUI = editor.plugins.get('BookmarkUI');
      const bookmarkFormView = bookmarkUI?.formView;

      if (!bookmarkFormView) {
        return;
      }

      const alreadyWrapped = bookmarkFormView.children.get(0)?.template?.attributes?.class?.includes('ck-bookmark-form_inner');

      if (alreadyWrapped) return;

      // Add a wrapper for existing form elements.
      const formElements = new View();

      formElements.setTemplate({
        tag: 'div',
        children: [...bookmarkFormView.children],
        attributes: {
          class: ['ck-bookmark-form_inner'],
        },
      });

      bookmarkFormView.children.clear();
      bookmarkFormView.children.add(formElements);

      // Add the help text.
      const helpText = new View();
      helpText.setTemplate({
        tag: 'div',
        children: [
          {
            tag: 'p',
            children: [
              {
                tag: 'strong',
                children: [
                  'To link to this anchor from within the current page: ',
                ],
              },
              'Don’t use the full URL. Instead use a number sign followed by the name of the anchor.',
            ],
          },
          // {
          //   tag: 'p',
          //   children: [
          //     {
          //       tag: 'button',
          //       attributes: {
          //         type: 'button',
          //         id: 'copy-anchor-name',
          //         title: 'Copy',
          //       },
          //       children: ['Copy'],
          //     },
          //   ],
          // },
          {
            tag: 'p',
            children: [
              {
                tag: 'strong',
                children: ['From another page: '],
              },
              'Use the full URL of this page followed by a number sign and the name of the anchor.',
            ],
          },
        ],
        attributes: {
          class: ['ck-bookmark-form_help-text'],
        },
      });

      bookmarkFormView.children.add(helpText);

      // Add the copy button to the anchor name.
      // const copyButton = helpText.element.querySelector('#copy-anchor-name');

      // if (!copyButton) {
      //   return;
      // }

      // copyButton.addEventListener('click', () => {
      //   const anchorName = anchorFormView.element.querySelector(
      //     '#anchor-name-placeholder',
      //   ).textContent;
      //   navigator.clipboard.writeText(anchorName);
      //   copyButton.setAttribute('disabled', true);
      //   const message = document.createElement('span');
      //   message.textContent = 'Copied!';
      //   copyButton.parentElement.appendChild(message);
      //   setTimeout(() => {
      //     message.remove();
      //     copyButton.removeAttribute('disabled');
      //   }, 3000);
      // });
    });
  }
}
