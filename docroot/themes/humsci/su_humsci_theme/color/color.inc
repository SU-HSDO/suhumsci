<?php

/**
 * @file
 * Lists available colors and color schemes for the su_humsci_theme theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'base' => t('Base'),
    'link' => t('Link'),
    'text' => t('Text'),

    'button' => t('Button Background'),
    'buttoncolor' => t('Button Color'),

    'branding' => t('Branding background'),
    'brandingcolor' => t('Branding color'),

    'header' => t('Header background'),
    'headertext' => t('Header text'),
    'headerlink' => t('Header link'),

    'main' => t('Main background'),
    'maintext' => t('Main text'),
    'mainlink' => t('Main links'),

    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
    'footerlink' => t('Footer links'),

    'globalfooter' => t('Global Footer background'),
    'globalfooterlink' => t('Global Footer color'),
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
        'buttoncolor' => '#fffefe',

        'branding' => '#b1040f',
        'brandingcolor' => '#fefefe',

        'header' => '#fffffe',
        'headertext' => '#2e2d2a',
        'headerlink' => '#b1050e',

        'main' => '#fffeff',
        'maintext' => '#2e2e29',
        'mainlink' => '#b2040e',

        'footer' => '#feffff',
        'footertext' => '#2f2d29',
        'footerlink' => '#b0040e',

        'globalfooter' => '#8c1515',
        'globalfooterlink' => '#fefeff',
      ],
    ],
    'night' => [
      'title' => t('Night'),
      'colors' => [
        'base' => '#000000',
        'link' => '#0db104',
        'text' => '#ffffff',

        'button' => '#00ece9',
        'buttoncolor' => '#b1040e',

        'branding' => '#ffffff',
        'brandingcolor' => '#b1040e',

        'header' => '#000000',
        'headertext' => '#ffffff',
        'headerlink' => '#ffffff',

        'main' => '#3b0536',
        'maintext' => '#ffffff',
        'mainlink' => '#00ece9',

        'footer' => '#1e1e1e',
        'footertext' => '#ffffff',
        'footerlink' => '#00ece9',

        'globalfooter' => '#8c1515',
        'globalfooterlink' => '#fefeff',
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
        // Put the logos path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'su_humsci_theme'),
        'globalFooterLogo' => '/' . \Drupal::service('extension.list.theme')->getPath( 'stanford_basic') . '/assets/svg/su_sig_stack_linked.svg',
        'userId' => \Drupal::currentUser()->id(),
      ],
    ],
  ],
];
