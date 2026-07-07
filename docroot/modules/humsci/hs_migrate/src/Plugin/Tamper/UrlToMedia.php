<?php

namespace Drupal\hs_migrate\Plugin\Tamper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\MediaInterface;
use Drupal\tamper\Attribute\Tamper;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\SourceDefinitionInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tamper plugin for converting image URLs to Drupal media entities.
 *
 * Downloads an image from a URL, saves it as a file, and returns a media
 * entity ID. Intended for use as a Tamper plugin to transform image URL source
 * values into Drupal media entity references during import.
 */
#[Tamper(
  id: 'url_to_media',
  label: new TranslatableMarkup('URL to Image Media Entity'),
  description: new TranslatableMarkup('Downloads an image from a URL and creates an image media entity. To include alt text use the format https://example.com/my-image.jpg|This is my alternative description of the image.'),
  category: new TranslatableMarkup('Media'),
)]
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
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

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
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceDefinitionInterface $source_definition, EntityTypeManagerInterface $entity_type_manager, FileSystemInterface $file_system, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, FileRepositoryInterface $file_repository, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $source_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->fileRepository = $file_repository;
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
      $container->get('file.repository'),
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
   *   The media entity ID, or NULL if there is no data to process.
   *
   * @throws \Drupal\tamper\Exception\TamperException
   */
  public function tamper($data, ?TamperableItemInterface $item = NULL) {
    // media_duplicate_validation fires on media insert and will block saves for
    // any image it considers a duplicate, causing imports to fail.
    if ($this->moduleHandler->moduleExists('media_duplicate_validation')) {
      $message = 'UrlToMedia tamper plugin cannot run with media_duplicate_validation module enabled. Please disable the module before running migrations.';
      $this->loggerFactory->get('hs_migrate')->error($message);
      throw new TamperException($message);
    }

    if (empty($data)) {
      return NULL;
    }

    if (!is_string($data)) {
      $this->loggerFactory->get('hs_migrate')->notice('Error processing URL to media: Received non-string data of type @type, skipping image import.', [
        '@type' => gettype($data),
      ]);
      return NULL;
    }

    // There may be multiple parts for URL and alt text.
    $parts = explode('|', $data, 2);
    $parts = array_map('trim', $parts);
    $image_url = $parts[0];
    $alt_text = $parts[1] ?? '';

    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
      throw new TamperException('Image URL is not a valid URL: ' . $image_url);
    }

    $host = parse_url($image_url, PHP_URL_HOST);
    $allowed_domains = ['stanford.edu'];
    $is_allowed = FALSE;
    foreach ($allowed_domains as $domain) {
      if ($host === $domain || str_ends_with($host, '.' . $domain)) {
        $is_allowed = TRUE;
        break;
      }
    }
    if (!$is_allowed) {
      throw new TamperException('Image URL host is not in the allowed domain list: ' . $host);
    }

    $path = parse_url($image_url, PHP_URL_PATH);
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $path)) {
      throw new TamperException('Image URL does not point to a file with a recognized image extension: ' . $image_url);
    }

    $file = $this->downloadFile($image_url);
    $media = $this->createMediaEntity($file, $alt_text);
    // Drupal media reference fields identify media by ID.
    return $media->id();
  }

  /**
   * Downloads a file from the given URL and returns a permanent File entity.
   *
   * @param string $url
   *   The URL to download from.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity.
   *
   * @throws \Drupal\tamper\Exception\TamperException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function downloadFile(string $url): FileInterface {
    $directory = 'public://media/image';
    $filename = basename(parse_url($url, PHP_URL_PATH));
    $destination = $directory . '/' . $filename;

    try {
      if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
        throw new TamperException('Could not create or write to directory: ' . $directory);
      }

      $response = $this->httpClient->request('GET', $url, ['timeout' => 20]);
      // The full response body is loaded into memory. This is acceptable given
      // the 5MB size limit enforced below, but means the limit relies on the
      // server providing the complete response before the check runs.
      $body = (string) $response->getBody();

      if (strlen($body) > 5 * 1024 * 1024) {
        throw new TamperException('File from ' . $url . ' exceeds the 5MB size limit: ' . round(strlen($body) / 1024 / 1024, 1) . 'MB');
      }

      return $this->fileRepository->writeData($body, $destination, FileExists::Rename);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_migrate')->error('Error downloading file from @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Creates an image media entity from a file entity.
   *
   * Creates a new media entity of type 'image' and links it to the
   * provided file entity using the appropriate source field.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity to create media from.
   * @param string $alt_text
   *   The alt text describing the image.
   *
   * @return \Drupal\media\MediaInterface
   *   The created media entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\tamper\Exception\TamperException
   */
  protected function createMediaEntity(FileInterface $file, string $alt_text): MediaInterface {
    try {
      /** @var \Drupal\media\Entity\MediaType $media_type */
      $media_type = $this->entityTypeManager->getStorage('media_type')->load('image');
      $source_field_name = $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();
      $media = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => 'image',
        'name' => $file->getFilename(),
        'status' => 1,
        // FileRepository::writeData() assigns the file to the current user or
        // anonymous (0). Media entity ownership is set explicitly here.
        'uid' => 1,
        $source_field_name => [
          'target_id' => $file->id(),
          'alt' => $alt_text,
        ],
      ]);
      $media->save();
      return $media;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('hs_migrate')->error('Failed to create media entity: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
