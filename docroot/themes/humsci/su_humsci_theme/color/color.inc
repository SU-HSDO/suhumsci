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
    'header' => t('Header background'),
    'branding' => t('Branding background'),
    'main' => t('Main background'),
    'footer' => t('Footer background'),
    'link' => t('Link'),
    'text' => t('Text'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Blue Lagoon (default)'),
      'colors' => [
        'base' => '#ffffff',
        'branding' => '#055a8e',
        'header' => '#1d84c3',
        'main' => '#ffffff',
        'footer' => '#f6f6f2',
        'link' => '#0071b3',
        'text' => '#000000',
      ],
    ],
    'firehouse' => [
      'title' => t('Firehouse'),
      'colors' => [
        'base' => '#ffffff',
        'branding' => '#cd2d2d',
        'header' => '#d64e4e',
        'main' => '#ffffff',
        'footer' => '#f1f4f0',
        'link' => '#d6121f',
        'text' => '#000000',
      ],
    ],
    'ice' => [
      'title' => t('Ice'),
      'colors' => [
        'base' => '#ffffff',
        'branding' => '#d0d0d0',
        'header' => '#c2c4c5',
        'main' => '#ffffff',
        'footer' => '#ffffff',
        'link' => '#019dbf',
        'text' => '#000000',
      ],
    ],
    'plum' => [
      'title' => t('Plum'),
      'colors' => [
        'base' => '#ffffff',
        'branding' => '#4c1c58',
        'header' => '#593662',
        'main' => '#fffdf7',
        'footer' => '#edede7',
        'link' => '#9d408d',
        'text' => '#000000',
      ],
    ],
    'slate' => [
      'title' => t('Slate'),
      'colors' => [
        'base' => '#ffffff',
        'branding' => '#4a4a4a',
        'header' => '#4e4e4e',
        'main' => '#ffffff',
        'footer' => '#ffffff',
        'link' => '#0073b6',
        'text' => '#000000',
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
      'colors' => ['branding', 'header'],
    ],
  ],
  // Preview files.
  'preview_library' => 'su_humsci_theme/color.preview',
  'preview_html' => 'color/preview.html',
  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'su_humsci_theme'),
        'footerLogo' => '/' . drupal_get_path('theme', 'stanford_basic') . '/assets/svg/su_sig_stack_linked.svg',
      ],
    ],
  ],
];
