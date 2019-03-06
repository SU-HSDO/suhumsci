<?php

namespace Drupal\hs_layouts\TwigExtension;

/**
 * Additional twig filters for custom functionality.
 *
 * @package Drupal\su_humsci_theme\TwigExtension
 */
class TwigFilters extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('has_markup', [$this, 'hasMarkup']),
    ];
  }

  /**
   * Remove all HTML inline comments.
   *
   * @param array|string $variable
   *   Render variable.
   *
   * @return string
   *   Render string without the html comments.
   */
  public static function removeHtmlComments($variable) {
    if (!is_string($variable)) {
      $variable = render($variable);
    }
    return preg_replace([
      '/<!--(.|\s)*?-->\s*/',
      '/\t+/',
      '/\n+/',
      '/[\r\n]/',
      '/[\n\r]/',
    ], '', $variable);
  }

  /**
   * Check if the provided variable has any visible markup.
   *
   * @param array|string $variable
   *   Render array or rendered string.
   *
   * @return bool
   *   If the variable contains any visible markup.
   */
  public static function hasMarkup($variable) {
    $string = static::removeHtmlComments($variable);
    $string = strip_tags($string, '<img> <a> <iframe> <object> <picture> <figure>');
    return !empty(trim($string));
  }

}
