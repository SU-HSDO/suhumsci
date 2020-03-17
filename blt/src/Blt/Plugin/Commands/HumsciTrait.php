<?php

namespace Example\Blt\Plugin\Commands;

use StanfordCaravan\Robo\Tasks\AcquiaApi;
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
   * Get initialized Acquia api object.
   *
   * @return \StanfordCaravan\Robo\Tasks\AcquiaApi
   *   Acquia API.
   */
  protected function getAcquiaApi() {
    $key = $this->getConfigValue('cloud.key') ?: $_ENV['ACP_KEY'];
    $secret = $this->getConfigValue('cloud.secret') ?: $_ENV['ACP_SECRET'];
    return new AcquiaApi($this->getConfigValue('cloud.appId'), $key, $secret);
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
