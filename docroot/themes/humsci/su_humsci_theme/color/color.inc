<?php
/**
 * @file
 * Lists available colors and color schemes for the su_humsci_theme theme.
 */
//_color_rewrite_stylesheet()

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'base' => t('Base'),
    'link' => t('Link'),
    'text' => t('Text'),

    'button' => t('Button Background'),
    'button_color' => t('Button Color'),

    'branding' => t('Branding background'),
    'branding_color' => t('Branding color'),

    'header' => t('Header background'),
    'header_text' => t('Header text'),
    'header_link' => t('Header link'),

    'main' => t('Main background'),
    'main_text' => t('Main text'),
    'main_link' => t('Main links'),

    'footer' => t('Footer background'),
    'footer_text' => t('Footer text'),
    'footer_link' => t('Footer links'),

    'global_footer' => t('Global Footer background'),
    'global_footer_link' => t('Global Footer color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Day (default)'),
      'colors' => [
        'base' => '#000000',
        'link' => '#b1040e',
        'text' => '#2e2d29',

        'button' => '#b1040d',
        'button_color' => '#fffefe',

        'branding' => '#b1040f',
        'branding_color' => '#fefefe',

        'header' => '#fffffe',
        'header_text' => '#2e2d2a',
        'header_link' => '#b1050e',

        'main' => '#fffeff',
        'main_text' => '#2e2e29',
        'main_link' => '#b2040e',

        'footer' => '#feffff',
        'footer_text' => '#2f2d29',
        'footer_link' => '#b0040e',

        'global_footer' => '#8c1515',
        'global_footer_link' => '#fefeff',
      ],
    ],
    'night' => [
      'title' => t('Night'),
      'colors' => [
        'base' => '#000000',
        'link' => '#b1040e',
        'text' => '#2e2d29',

        'button' => '#b1040e',
        'button_color' => '#ffffff',

        'branding' => '#b1040e',
        'branding_color' => '#ffffff',

        'header' => '#ffffff',
        'header_text' => '#2e2d29',
        'header_link' => '#b1040e',

        'main' => '#fffeff',
        'main_text' => '#2e2d29',
        'main_link' => '#b1040e',

        'footer' => '#ffffff',
        'footer_text' => '#2e2d29',
        'footer_link' => '#b1040e',

        'global_footer' => '#8c1515',
        'global_footer_link' => '#ffffff',
      ],
    ],
  ],
  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/colors.css',
  ],
  // Files to copy.
  'copy' => [
    'logo.svg',
  ],
  // Gradient definitions.
  'gradients' => [
    [
      // (x, y, width, height).
      'dimension' => [0, 0, 0, 0],
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      //      'colors' => ['branding', 'header'],
    ],
  ],
  // Preview files.
  'preview_library' => 'su_humsci_theme/color.preview',
  'preview_html' => 'color/preview.html',
  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logos path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'su_humsci_theme'),
        'globalFooterLogo' => '/' . drupal_get_path('theme', 'stanford_basic') . '/assets/svg/su_sig_stack_linked.svg',
        'userId' => \Drupal::currentUser()->isAuthenticated(),
      ],
    ],
  ],
];
