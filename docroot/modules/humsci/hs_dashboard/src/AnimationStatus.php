<?php

namespace Drupal\hs_dashboard;

/**
 * Animation status enum.
 */
enum AnimationStatus: string {
  case Enabled = 'enabled';
  case Disabled = 'disabled';
  case NotSet = 'not set';

  /**
   * Gets the animation status for a theme.
   *
   * @param string $theme_name
   *   The name of the theme to check.
   *
   * @return self
   *   The animation status.
   */
  public static function fromTheme(string $theme_name): self {
    $animation_setting = theme_get_setting('animation_toggle', $theme_name);

    if ($animation_setting === NULL) {
      return self::NotSet;
    }
    elseif ($animation_setting) {
      return self::Enabled;
    }
    else {
      return self::Disabled;
    }
  }

}
