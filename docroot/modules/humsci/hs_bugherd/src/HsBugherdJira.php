<?php

namespace Drupal\hs_bugherd;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jira_rest\JiraRestWrapperService;

class HsBugherdJira {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\hs_bugherd\HsBugherd
   */
  protected $bugherd;

  /**
   * @var \Drupal\jira_rest\JiraRestWrapperService
   */
  protected $jiraRestWrapper;

  /**
   * HsBugherdJira constructor.
   *
   * @param \Drupal\hs_bugherd\HsBugherd $bugherd
   * @param \Drupal\jira_rest\JiraRestWrapperService $jira
   */
  public function __construct(ConfigFactoryInterface $config_factory, HsBugherd $bugherd, JiraRestWrapperService $jira) {
    $this->configFactory = $config_factory;
    $this->bugherd = $bugherd;
    $this->jiraRestWrapper = $jira;
  }

  public function test() {
    $jira_project = $this->configFactory->get('bugherdapi.settings')
      ->get('jira_project');
    $issue_service = $this->jiraRestWrapper->getIssueService();
    $search = $issue_service->createSearch();
    $search->search("project = $jira_project");
    dpm($search->getIssues());
  }

  public function sendToJira($data) {

  }

  public function sendToBugherd($data) {

  }

}
