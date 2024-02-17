<?php

namespace Humsci\Blt\Plugin\Commands;

/**
 * Trait HsCommandTrait.
 *
 * @package Humsci\Blt\Plugin\Commands
 */
trait HsCommandTrait {

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
   * Return BLT.
   *
   * @return \Robo\Task\Base\Exec
   *   A drush exec command.
   */
  protected function blt() {
    return $this->taskExec($this->getConfigValue('repo.root') . '/vendor/bin/blt')
      ->option('no-interaction');
  }

}
