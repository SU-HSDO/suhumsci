<?php

namespace Drupal\hs_media_download\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Utility\Error;
use Drupal\media_entity_download\Events\MediaDownloadEvent;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download Media Entity Download protected files on locals.
 *
 * This is based on an event subscriber currently under development.
 * It's current state fits our needs so we're storing the patch file
 * 'patches/media_entity_download-2951316.patch' to avoid potential
 * breaking changes in the MR.
 *
 * @see Drupal\stage_file_proxy\DownloadManager
 * @see https://www.drupal.org/project/media_entity_download/issues/2951316#comment-14340898
 */
final class DocumentStageFileProxySubscriber implements EventSubscriberInterface {

  /**
   * The origin server URL, provided by Stage File Proxy.
   *
   * @var string
   */
  private string $origin;

  /**
   * The lock backend used to prevent concurrent upstream fetches.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private LockBackendInterface $lock;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private ClientInterface $client;

  /**
   * The filesystem.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $filesystem;

  /**
   * The system logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Construct a new DocumentStageFileProxySubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory used to get the upstream file origin hostname.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend used to preven concurrent upstream fetches.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP cliet.
   * @param \Drupal\Core\File\FileSystemInterface $filesystem
   *   The filesystem used to save the remote file.
   * @param \Psr\Log\LoggerInterface $logger
   *   They system logger.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LockBackendInterface $lock, ClientInterface $client, FileSystemInterface $filesystem, LoggerInterface $logger) {
    $this->origin = $configFactory->get('stage_file_proxy.settings')->get('origin');
    $this->lock = $lock;
    $this->client = $client;
    $this->filesystem = $filesystem;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MediaDownloadEvent::EVENT_NAME => 'onMediaDownload',
    ];
  }

  /**
   * Download media entity download files if needed.
   *
   * @param \Drupal\media_entity_download\Events\MediaDownloadEvent $event
   *   The event triggered when downloading the media item.
   */
  public function onMediaDownload(MediaDownloadEvent $event) {
    if (file_exists($event->getUri())) {
      return;
    }

    $url = $this->origin . $event->getRequest()->getRequestUri();

    $lock_id = 'hs_media_download: ' . md5($url);
    while(!$this->lock->acquire($lock_id)) {
      $this->lock->wait($lock_id, 1);
    }

    try {
      // Fetch remote file.
      $options ['Connection'] = 'close';
      $response = $this->client->get($url, $options);
      $result = $response->getStatusCode();

      if ($result !== Response::HTTP_OK) {
        $this->logger->warning('HTTP error @errorcode occurred when trying to fetch @remote.', [
          '@errorcode' => $result,
          '@remote' => $url,
        ]);
        $this->lock->release($lock_id);
        return;
      }

      $directory = $this->filesystem->dirname($event->getUri());

      if (!$this->filesystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        $this->logger->error('Unable to prepare local directory @path.', [
          '@path' => $directory,
        ]);
        $this->lock->release($lock_id);
        return;
      }

      $content_length_headers = $response->getHeader('Content-Length');
      $content_length = (int) array_shift($content_length_headers);
      $response_data = $response->getBody()->getContents();

      if(isset($content_length) && strlen($response_data) !== $content_length) {
        $this->logger->error('Incomplete download. Was expecting @content-length bytes, actually got @data-length.', [
          '@content-length' => $content_length,
          '@data-length' => $content_length,
        ]);
        $this->lock->release($lock_id);
        return;
      }

      if ($this->writeFile($event->getUri(), $response_data)) {
        $this->lock->release($lock_id);
        return;
      }

      $this->logger->error('@remote could not be saved to @path.',
        [
          '@remote' => $url,
          '@path' => $directory,
        ]);
      $this->lock->release($lock_id);
      return;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Encountered an error when retrieving file @url. @message in %function (line %line of %file).', Error::decodeException($e) + ['@url' => $url]);
      $this->lock->release($lock_id);
      return;
    }
  }

  /**
   * Use write & rename, not just write.
   *
   * Perform the replace operation. Since there could be multiple processes
   * writing to the same file, the best option is to create a temporary file in
   * the same directory and then rename it to the destination. A temporary file
   * is needed if the directory is mounted on a separate machine.
   *
   * @param string $destination
   *   A strnig containnig the destination location.
   * @param string $data
   *   A string containing the contents of the file.
   *
   * @return bool
   *   True if write was successful. False if write or rename failed.
   */
  private function writeFile(string $destination, string $data) {
    $dir = $this->filesystem->dirname($destination) . '/';
    $temporary_file = $this->filesystem->tempnam($dir, 'stage_file_proxy_');
    $temporary_file_copy = $temporary_file;

    $parts = pathinfo($destination);
    $extension = '.' . $parts['extension'];
    if ($extension === '.gz') {
      $parts = pathinfo($parts['filename']);
      $extension = '.' . $parts['extension'] . $extension;
    }
    $temporary_file = str_replace(substr($temporary_file, 0, strpos($temporary_file, 'stage_file_proxy_')), $dir, $temporary_file) . $extension;

    if (!@rename($temporary_file_copy, $temporary_file)) {
      @unlink($temporary_file_copy);
      return FALSE;
    }

    $filepath = $this->filesystem->saveData($data, $temporary_file, FileSystemInterface::EXISTS_REPLACE);
    if ($filepath) {
      if (!@rename($filepath, $destination)) {
        @unlink($destination);
        if (!@rename($filepath, $destination)) {
          @unlink($filepath);
        }
      }
    }
    if (file_exists($destination) && filesize($destination) > 0) {
      return TRUE;
    }
    return FALSE;
  }
}
