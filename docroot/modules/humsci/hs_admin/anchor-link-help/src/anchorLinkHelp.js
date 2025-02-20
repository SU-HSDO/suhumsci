import { Plugin } from 'ckeditor5/src/core';
import { View } from 'ckeditor5/src/ui';

export default class AnchorLinkHelp extends Plugin {
  /**
   * @inheritDoc
   */
  static get pluginName() {
    return 'AnchorLinkHelp';
  }

  /**
   * @inheritDoc
   */
  static get requires() {
    return ['Anchor'];
  }

  init() {
    const { editor } = this;

    const anchorFormView = editor.plugins.get('AnchorUI')?.formView;

    if (!anchorFormView) {
      return;
    }

    // Add a wrapper for existing form elements.
    const formElements = new View();

    formElements.setTemplate({
      tag: 'div',
      children: [...anchorFormView.children],
      attributes: {
        class: ['ck-anchor-form_inner'],
      },
    });

    anchorFormView.children.clear();
    anchorFormView.children.add(formElements);

    // Get the input field.
    const input = formElements.template.children[0]?.fieldView;

    if (!input) {
      return;
    }

    // Update the anchor name placeholder when the input changes.
    input.on('input', (event) => {
      const placeholder = anchorFormView.element.querySelector(
        '#anchor-name-placeholder',
      );
      const anchorName = event.source.element.value;
      placeholder.textContent = `#${anchorName || '[anchor-name]'}`;
      if (anchorName) {
        placeholder.dataset.anchorNameSet = '';
      }
    });

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
        {
          tag: 'p',
          children: [
            {
              tag: 'span',
              attributes: {
                id: 'anchor-name-placeholder',
              },
              children: ['#[anchor-name]'],
            },
            {
              tag: 'button',
              attributes: {
                type: 'button',
                id: 'copy-anchor-name',
                title: 'Copy',
              },
              children: ['Copy'],
            },
          ],
        },
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
        class: ['ck-anchor-form_help-text'],
      },
    });

    anchorFormView.children.add(helpText);

    // Update the anchor name placeholder when the form view is shown.
    editor.plugins
      .get('ContextualBalloon')
      .on('set:visibleView', (event, propertyName, newValue, oldValue) => {
        if (newValue === oldValue || newValue !== anchorFormView) {
          return;
        }
        const placeholder = anchorFormView.element.querySelector(
          '#anchor-name-placeholder',
        );
        placeholder.textContent = '#[anchor-name]';
        placeholder.removeAttribute('data-anchor-name-set');
      });

    // Add the copy button to the anchor name.
    const copyButton = helpText.element.querySelector('#copy-anchor-name');

    if (!copyButton) {
      return;
    }

    copyButton.addEventListener('click', () => {
      const anchorName = anchorFormView.element.querySelector(
        '#anchor-name-placeholder',
      ).textContent;
      navigator.clipboard.writeText(anchorName);
      copyButton.setAttribute('disabled', true);
      const message = document.createElement('span');
      message.textContent = 'Copied!';
      copyButton.parentElement.appendChild(message);
      setTimeout(() => {
        message.remove();
        copyButton.removeAttribute('disabled');
      }, 3000);
    });
  }
}
