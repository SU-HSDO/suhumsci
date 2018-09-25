<?php

namespace Acquia\Blt\Custom\Commands;

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

}
