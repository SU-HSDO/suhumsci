(function (Drupal, once) {
  // Elements that convey visible content even without text, so a block
  // containing one of these shouldn't be treated as empty.
  const VISIBLE_CONTENT_SELECTOR = 'img, svg, picture, video, iframe, canvas, audio, embed, object, input, button, select, textarea, [role="img"]';

  Drupal.behaviors.emptyBlockDetection = {
    attach(context) {
      // Some blocks (e.g. the "Title" field block, which renders nothing on
      // the front page) can still print an empty wrapper div with no
      // visible content. CSS :empty can't reliably catch this because Twig
      // leaves whitespace text nodes between the tags (which can still
      // create a phantom line box even with margin/height zeroed out), so
      // remove the block outright instead of just hiding it with CSS.
      once('empty-block-detection', '.block-layout-builder', context).forEach((block) => {
        const title = block.querySelector('.block__title');

        if (title && title.textContent.trim() !== '') {
          return;
        }

        if (block.textContent.trim() !== '' || block.querySelector(VISIBLE_CONTENT_SELECTOR)) {
          return;
        }

        block.remove();
      });
    },
  };
}(Drupal, once));
