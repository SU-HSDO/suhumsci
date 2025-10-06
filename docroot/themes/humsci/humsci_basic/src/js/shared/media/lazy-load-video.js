// In order to style the figcaption, figure elements have a display: table.
// This causes issues when there is a video in a figure because the video no longer
// fills the entire space of the container.
// This JS sets a width of 100% to figures that contain videos.
(function (Drupal, once) {
  Drupal.behaviors.videoLazyBehavior = {
    attach(context) {
      const videos = once('video-lazy', '.video-lazy', context);

      if (videos && videos.length > 0) {
        videos.forEach((video) => {
          const playButton = video.querySelector('.video-play');

          const loadIframe = () => {
            const provider = video.dataset.videoProvider;
            const { videoId } = video.dataset;
            let embedUrl = '';

            if (provider === 'youtube') {
              embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
            } else if (provider === 'vimeo') {
              embedUrl = `https://player.vimeo.com/video/${videoId}?autoplay=1`;
            }

            const iframe = document.createElement('iframe');
            iframe.src = embedUrl;
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'autoplay; encrypted-media');
            iframe.setAttribute('allowfullscreen', 'true');
            iframe.style.width = '100%';
            iframe.style.height = '100%';
            iframe.className = 'video-iframe';

            video.innerHTML = '';
            video.appendChild(iframe);
          };

          playButton.addEventListener('click', loadIframe);
        });
      }
    },
  };
}(Drupal, once));
