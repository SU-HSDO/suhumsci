<?php

namespace Drupal\hs_migrate\Plugin\Tamper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperBase;
use Drupal\tamper\TamperableItemInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tamper plugin for converting image URLs to Drupal media entities.
 *
 * This plugin downloads images from external URLs during data import and
 * creates corresponding Drupal media entities. It's designed for use with
 * the Feeds module to automatically import images referenced in CSV/XML data.
 *
 * IMPORTANT: This plugin will only be discovered and loaded on sites that have
 * the Tamper module enabled. On sites without the Tamper module, this file
 * will sit inert and cause no issues or conflicts. The plugin system ensures
 * that plugins are only loaded when their required modules are present.
 *
 * @Tamper(
 * id = "url_to_media",
 * label = @Translation("URL to Image Media Entity"),
 * description = @Translation("Downloads an image from a URL and creates an image media entity."),
 * category = "Media"
 * )
 */
class UrlToMedia extends TamperBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructs a UrlToMedia tamper plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tamper\SourceDefinitionInterface $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceDefinitionInterface $source_definition, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $source_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $configuration['source_definition'],
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Converts a URL string to a media entity ID.
   *
   * Validates the URL, downloads the image, creates a file entity,
   * and returns the ID of the created media entity.
   *
   * @param mixed $data
   *   The URL string to process.
   * @param \Drupal\tamper\TamperableItemInterface|null $item
   *   The item being processed (unused).
   *
   * @return int|null
   *   The media entity ID on success, NULL on failure.
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // Known issue: only can run tamper when module is disabled.
    if ($this->moduleHandler->moduleExists('media_duplicate_validation')) {
      $this->loggerFactory->get('hs_migrate')->error('UrlToMedia tamper plugin cannot run with media_duplicate_validation module enabled. Please disable the module before running migrations.');
      return NULL;
    }

    if (empty($data) || !is_string($data) || !filter_var($data, FILTER_VALIDATE_URL)) {
      return NULL;
    }

    // Skip if URL doesn't appear to be an image.
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $data)) {
      return NULL;
    }

    try {
      $file = $this->downloadFile($data);
      $media = $this->createMediaEntity($file);
      return $media ? $media->id() : NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_migrate')->error('Error processing URL to media for @url: @message', [
        '@url' => $data,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Downloads a file from the given URL and creates a permanent File entity.
   *
   * @param string $url
   *   The URL to download from.
   *
   * @return \Drupal\file\Entity\File|null
   *   The created permanent file entity or NULL on failure.
   */
  protected function downloadFile(string $url): ?File {
    try {
      $response = $this->httpClient->get($url, ['timeout' => 60]);
      if ($response->getStatusCode() !== 200) {
        throw new TamperException('Download failed for ' . $url . ' with status code: ' . $response->getStatusCode());
      }

      // Basic file size check (5MB limit to prevent memory issues).
      $content_length = $response->getHeader('Content-Length')[0] ?? 0;
      if ($content_length > 5 * 1024 * 1024) {
        throw new TamperException('File too large for ' . $url . ': ' . round($content_length / 1024 / 1024, 1) . 'MB');
      }

      // Extract filename from URL and create destination path.
      $filename = basename(parse_url($url, PHP_URL_PATH)) ?: 'downloaded_file';
      $destination = 'public://media/image/' . $filename;
      $this->fileSystem->saveData((string) $response->getBody(), $destination, FileExists::Rename);
      $file = File::create([
        'uri' => $destination,
        'status' => 1,
        'uid' => 1,
        'filename' => basename($destination),
        // @phpstan-ignore-next-line
        // Static call due to proxy class issues.
        'filemime' => \Drupal::service('file.mime_type.guesser')->guessMimeType($destination),
      ]);
      $file->save();
      return $file;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_migrate')->error('Error downloading file from @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Creates an image media entity from a file entity.
   *
   * Creates a new media entity of type 'image' and links it to the
   * provided file entity using the appropriate source field.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity to create media from.
   *
   * @return \Drupal\media\Entity\Media|null
   *   The created media entity or NULL on failure.
   */
  protected function createMediaEntity(File $file): ?Media {
    try {
      $media_type = $this->entityTypeManager->getStorage('media_type')->load('image');
      $source_field_name = $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();
      $media = Media::create([
        'bundle' => 'image',
        'name' => $file->getFilename(),
        'status' => 1,
        'uid' => 1,
        $source_field_name => ['target_id' => $file->id()],
      ]);
      $media->save();
      return $media;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_migrate')->error('Failed to create media entity: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
