<?php

namespace Drupal\hs_field_helpers\Plugin\audio_embed_field\Provider;

use Drupal\audio_embed_field\Plugin\audio_embed_field\Provider\SoundCloud;
use GuzzleHttp\Exception\ClientException;

/**
 * Changes the SoundCloud provider so that it doesn't require a client ID.
 *
 * This should be removed when
 * https://www.drupal.org/project/audio_embed_field/issues/3007479 is resolved.
 */
class StanfordSoundCloud extends SoundCloud {

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $video_data = static::getVideoData($this->getInput());
    if (!$video_data) {
      return NULL;
    }
    return $video_data['thumbnail_url'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $video_data = static::getVideoData($this->getInput());
    return $video_data['title'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    if (!$input || !($video_data = static::getVideoData($input))) {
      return NULL;
    }

    libxml_use_internal_errors(TRUE);
    /** @var \DOMDocument $dom_document */
    $dom_document = new \DOMDocument();
    $dom_document->loadHTML($video_data['html']);

    $xpath = new \DOMXPath($dom_document);
    $src = urldecode($xpath->query('//iframe/@src')->item(0)->nodeValue);

    $parts = parse_url($src);
    parse_str($parts['query'], $query);
    $tracks_parts = explode('/', $query['url']);

    return (int) end($tracks_parts) ? end($tracks_parts) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $render = parent::renderEmbedCode($width, $height, $autoplay);
    $video_data = static::getVideoData($this->input);

    // Fix the url if the input is actually a playlist instead of a track.
    if (strpos($video_data['html'], 'playlist') !== FALSE) {
      $render['#url'] = str_replace('/tracks/', '/playlists/', $render['#url']);
    }

    return $render;
  }

  /**
   * Get data array from SoundCloud for a share url.
   *
   * @param string $video_url
   *   Url to audio page.
   *
   * @return array|null
   *   Keyed array of video data, null if invalid.
   */
  protected static function getVideoData($video_url) {
    $cache = \Drupal::cache('default');
    if ($cache_item = $cache->get('audio_embed_field:' . md5($video_url))) {
      return $cache_item->data;
    }

    try {
      $client = \Drupal::service('http_client');
      $res = $client->request('GET', 'http://soundcloud.com/oembed', [
        'query' => [
          'format' => 'json',
          'url' => $video_url,
        ],
      ]);
    }
    catch (ClientException $e) {
      return NULL;
    }

    $video_data = json_decode($res->getBody(), TRUE);
    if (!isset($video_data['html'])) {
      return NULL;
    }

    // Cache audio data for 1 month.
    $cache->set('audio_embed_field:' . md5($video_url), $video_data, time() + 60 * 60 * 24 * 30);
    return $video_data;
  }

}
