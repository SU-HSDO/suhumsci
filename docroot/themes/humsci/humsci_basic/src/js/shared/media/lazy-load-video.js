(function (Drupal, once) {
  Drupal.behaviors.videoLazyBehavior = {
    attach(context) {
      const videos = once('video-lazy', '.hb-media-video', context);

      videos.forEach((video) => {
        const videoWrapper = video.querySelector('.hb-video-lazy');
        const videoUrl = videoWrapper.dataset.video;
        const thumb = video.querySelector('.hb-video-lazy__thumb');

        // Click to load iframe
        const playButton = video.querySelector('.hb-video-lazy__play');
        playButton.addEventListener('click', () => {
          let embedUrl = '';
          let videoId = '';
          let provider = '';
          let isPlaylist = false;

          // --- Determine provider and video ID ---
          if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
            provider = 'youtube';
            if (videoUrl.includes('list=')) {
              // Playlist
              const listMatch = videoUrl.match(/[?&]list=([^&]+)/);
              videoId = listMatch ? listMatch[1] : '';
              isPlaylist = true;
            } else {
              // Regular video
              const idMatch = videoUrl.match(/(?:v=|youtu\.be\/)([^?&]+)/);
              videoId = idMatch ? idMatch[1] : '';
            }
          } else if (videoUrl.includes('vimeo.com')) {
            provider = 'vimeo';
            const idMatch = videoUrl.match(/vimeo\.com\/(\d+)/);
            videoId = idMatch ? idMatch[1] : '';
          }

          // --- Build final embed URL ---
          if (provider === 'youtube') {
            embedUrl = isPlaylist
              ? `https://www.youtube.com/embed/videoseries?list=${videoId}&autoplay=1`
              : `https://www.youtube.com/embed/${videoId}?autoplay=1`;
          } else if (provider === 'vimeo') {
            embedUrl = `https://player.vimeo.com/video/${videoId}?autoplay=1`;
          }

          if (!embedUrl) return;

          // --- Replace image thumbnail with iframe ---
          const iframe = document.createElement('iframe');
          iframe.src = embedUrl;
          iframe.frameBorder = '0';
          iframe.allow = 'autoplay; fullscreen';
          iframe.allowFullscreen = true;
          iframe.classList.add('hb-video-lazy__iframe');

          thumb.replaceWith(iframe);
        });
      });

      if (videos && videos.length > 0) {
        for (let i = 0; i < videos.length; i++) {
          const video = videos[i];
          if (
            video.parentNode
            && video.parentNode.nodeName === 'FIGURE'
          ) {
            const figure = video.parentNode;

            if (figure.classList.contains('caption')) {
              figure.style.width = '100%';
            }
          }
        }
      }
    },
  };
}(Drupal, once));
