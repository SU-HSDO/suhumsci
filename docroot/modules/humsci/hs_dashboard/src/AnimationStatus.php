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
   * Gets the animation status from a theme's animation_toggle setting value.
   *
   * @param mixed $animation_setting
   *   The raw value of the theme's animation_toggle setting.
   *
   * @return self
   *   The animation status.
   */
  public static function fromSetting(mixed $animation_setting): self {
    return match ($animation_setting) {
      NULL => self::NotSet,
      TRUE, 1 => self::Enabled,
      FALSE, 0 => self::Disabled,
      default => self::NotSet,
    };
  }

}
