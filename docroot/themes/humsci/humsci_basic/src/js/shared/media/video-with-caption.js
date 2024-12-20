// In order to style the figcaption, figure elements have a display: table.
// This causes issues when there is a video in a figure because the video no longer
// fills the entire space of the container.
// This JS sets a width of 100% to figures that contain videos.
(function (Drupal, once) {
  Drupal.behaviors.videoWithCaptionBehavior = {
    attach(context) {
      const videos = once(
        'video-with-caption',
        '.field-media-oembed-video',
        context,
      );

      if (videos && videos.length > 0) {
        for (let i = 0; i < videos.length; i++) {
          const video = videos[i];
          if (
            video.parentNode
            && video.parentNode.parentNode
            && video.parentNode.parentNode.nodeName === 'FIGURE'
          ) {
            const figure = video.parentNode.parentNode;

            if (figure.classList.contains('caption')) {
              figure.style.width = '100%';
            }
          }
        }
      }
    },
  };
}(Drupal, once));
