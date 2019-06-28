<?php

namespace Acquia\Blt\Custom\Commands;

use Drupal\Core\Serialization\Yaml;
use Zend\Stdlib\Glob;

/**
 * Trait HumsciTrait.
 *
 * Commmonly used methods used in our custom BLT commands.
 *
 * @package Acquia\Blt\Custom\Commands
 */
trait HumsciTrait {

  /**
   * Recursive glob.
   *
   * @param string $pattern
   *   Glob pattern.
   * @param int $flags
   *   Globl flags.
   *
   * @return array|void
   *   Response from glob.
   */
  protected function rglob($pattern, $flags = 0) {
    $files = Glob::glob($pattern, $flags);
    foreach (Glob::glob(dirname($pattern) . '/*', Glob::GLOB_ONLYDIR | Glob::GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
  }

  /**
   * Ask a question to the user.
   *
   * @param string $question
   *   The question to ask.
   * @param string $default
   *   Default value.
   * @param bool $required
   *   If a response is required.
   *
   * @return string
   *   Response to the question.
   */
  protected function askQuestion($question, $default = '', $required = FALSE) {
    if ($default) {
      $response = $this->askDefault($question, $default);
    }
    else {
      $response = $this->ask($question);
    }
    if ($required && !$response) {
      return $this->askQuestion($question, $default, $required);
    }
    return $response;
  }

  /**
   * Git the information of the github remote.
   *
   * @return array
   *   Keyed array with github owner and name.
   */
  protected function getGitHubInfo() {
    $git_remote = exec('git config --get remote.origin.url');
    $git_remote = str_replace('.git', '', $git_remote);
    if (strpos($git_remote, 'https') !== FALSE) {
      $parsed_url = parse_url($git_remote);
      list($owner, $repo_name) = explode('/', trim($parsed_url['path'], '/'));
      return ['owner' => $owner, 'name' => $repo_name];
    }
    list(, $repo_name) = explode(':', $git_remote);
    str_replace('.git', '', $git_remote);

    list($owner, $repo_name) = explode('/', $repo_name);
    return ['owner' => $owner, 'name' => $repo_name];
  }

  /**
   * Get the last version from the profile.
   *
   * @return string
   *   Last semver version.
   */
  protected function getLastVersion() {
    $profile_info = Yaml::decode($this->getConfigValue('docroot') . '/profiles/humsci/su_humsci_profile/su_humsci_profile.info.yml');
    return $profile_info['version'];
  }

  /**
   * Advance to the next SemVer version.
   *
   * The behavior depends on the parameter $stage.
   *   - If $stage is empty, then the patch or minor version of $version is
   *     incremented
   *   - If $stage matches the current stage in the current version, then add
   *     one to the stage (e.g. alpha3 -> alpha4)
   *   - If $stage does not match the current stage in the current version, then
   *     reset to '1' (e.g. alpha4 -> beta1)
   *
   * Taken from consolidation/robo library.
   *
   * @param string $version
   *   A SemVer version.
   * @param string $stage
   *   Release stage: dev, alpha, beta, rc or an empty string for stable.
   *
   * @return string
   *   New semver version.
   */
  protected function incrementVersion($version, $stage = '') {
    $stable = empty($stage);

    preg_match('/-([a-zA-Z]*)([0-9]*)/', $version, $match);
    $match += ['', '', ''];
    $versionStage = $match[1];
    $versionStageNumber = $match[2];
    if ($versionStage != $stage) {
      $versionStageNumber = 0;
    }
    $version = preg_replace('/-.*/', '', $version);
    $versionParts = explode('.', $version);
    if ($stable) {
      $versionParts[count($versionParts) - 1]++;
    }
    $version = implode('.', $versionParts);
    if (!$stable) {
      $version .= '-' . $stage;
      if ($stage != 'dev') {
        $versionStageNumber++;
        $version .= $versionStageNumber;
      }
    }
    return $version;
  }

}
