<?php

namespace Acquia\Blt\Custom\Commands;

/**
 * Trait HumsciTrait.
 *
 * @package Acquia\Blt\Custom\Commands
 */
trait HumsciTrait {

  /**
   * Recusive glob.
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
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->rglob($dir . '/' . basename($pattern), $flags));
    }
    return $files;
  }

}
