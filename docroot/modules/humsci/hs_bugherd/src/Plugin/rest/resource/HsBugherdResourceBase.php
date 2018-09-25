<?php

namespace Drupal\hs_bugherd\Plugin\rest\resource;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\hs_bugherd\HsBugherd;
use Drupal\jira_rest\JiraRestWrapperService;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BugherdResourceBase.
 *
 * @package Drupal\hs_bugherd\Plugin\rest\resource
 */
abstract class HsBugherdResourceBase extends ResourceBase {

  /**
   * Bugeherd API service.
   *
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherdApi;

  /**
   * Jira API service.
   *
   * @var \biologis\JIRA_PHP_API\IssueService
   */
  protected $jiraApi;

  /**
   * Connection config entity with settings for Bugherd and Jira.
   *
   * @var \Drupal\hs_bugherd\Entity\BugherdConnectionInterface
   */
  protected $bugherdConnection;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('hs_bugherd'),
      $container->get('jira_rest_wrapper_service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, HsBugherd $bugherd_api, JiraRestWrapperService $jira_api, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->bugherdApi = $bugherd_api;
    $this->jiraApi = $jira_api->getIssueService();
    $this->configFactory = $config_factory;
  }

}
