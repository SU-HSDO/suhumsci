<?php

namespace Drupal\hs_dashboard;

/**
 * Animation status enum for theme animation settings.
 *
 * This enum represents the possible states of animation settings for a theme:
 * - Enabled: Animations are explicitly enabled.
 * - Disabled: Animations are explicitly disabled.
 * - NotSet: Animation setting is not configured.
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
    return match ($animation_setting) {
      NULL => self::NotSet,
      TRUE, 1 => self::Enabled,
      FALSE, 0 => self::Disabled,
      default => self::NotSet,
    };
  }

}
