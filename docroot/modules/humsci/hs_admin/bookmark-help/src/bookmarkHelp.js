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

    balloon.on('set:visibleView', () => {
      const bookmarkUI = editor.plugins.get('BookmarkUI');
      const bookmarkFormView = bookmarkUI?.formView;

      if (!bookmarkFormView) {
        return;
      }

      // Check if there's already a wrapper class for the form to avoid adding another one.
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

      // Get the input field wrapper.
      const inputWrapper = formElements.template.children[1]?.template.children[0]['_items'];

      if (!inputWrapper) {
        return;
      }

      // Remove the status text
      inputWrapper[0]['_statusText'] = '';

      // Add a copy functionality into the form
      const copyView = new View();
      copyView.setTemplate({
        tag: 'div',
        attributes: {
          class: 'ck-bookmark-form__wrapper',
        },
        children: [
          ...inputWrapper[0].fieldWrapperChildren,
          {
            tag: 'button',
            attributes: {
              type: 'button',
              id: 'copy-bookmark-name',
              title: 'Copy',
            },
            children: ['Copy'],
          },
        ],
      });

      inputWrapper[0].fieldWrapperChildren.clear();
      inputWrapper[0].fieldWrapperChildren.add(copyView);

      // Get the input
      const input = inputWrapper[0]?.fieldView;

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
                  'To link to this bookmark: ',
                ],
              },
            ],
          },
          {
            tag: 'ul',
            children: [
              {
                tag: 'li',
                children: [
                  "From within the current page: Don't use the full URL. Instead use a number sign followed by the name of the anchor.",
                ],
              },
              {
                tag: 'li',
                children: [
                  "From another page: Use the full URL of this page followed by a number sign and the name of the anchor.",
                ],
              },
            ],
          },
        ],
        attributes: {
          class: ['ck-bookmark-form_help-text'],
        },
      });

      bookmarkFormView.children.add(helpText);

      // Add the copy button to the anchor name.
      const copyButton = copyView.element.querySelector('#copy-bookmark-name');

      if (!copyButton) {
        return;
      }

      copyButton.addEventListener('click', () => {
        const bookmarkName = input.element.value.trim();
        navigator.clipboard.writeText(bookmarkName);
        copyButton.setAttribute('disabled', true);

        const message = document.createElement('span');
        message.classList.add('copy-bookmark-message');
        message.textContent = 'Copied to the clipboard!';
        copyButton.parentElement.appendChild(message);
        setTimeout(() => {
          message.remove();
          copyButton.removeAttribute('disabled');
        }, 3000);
      });
    });
  }
}
