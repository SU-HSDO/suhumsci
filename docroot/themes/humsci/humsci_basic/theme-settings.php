<?php

/**
 * @file
 * Provides an additional config form for theme settings.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

// Set theme name to use in the key values.
$theme_name = \Drupal::theme()->getActiveTheme()->getName();

/**
 * Implements hook_form_system_theme_settings_alter().
 *
 * Form override for theme settings.
 */
function humsci_basic_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state) {
  $form['options_settings'] = [
    '#type' => 'fieldset',
    '#title' => t('Theme Specific Settings'),
  ];

  // Brandbar
  $form['options_settings']['humsci_basic_brand_bar'] = [
    '#type' => 'fieldset',
    '#title' => t('Brand Bar Settings'),
  ];

  $form['options_settings']['humsci_basic_brand_bar']['brand_bar_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Brand Bar Variant'),
    '#options' => [
      'default' => '- Default -',
      'bright' => t('Bright'),
      'dark' => t('Dark'),
      'white' => t('White'),
    ],
    '#default_value' => theme_get_setting('brand_bar_variant_classname'),
  ];
  $theme_path = \Drupal::service('extension.list.theme')->getPath('humsci_basic');
  // Lockup
  $img = '<img src="' . base_path() . $theme_path . '/lockup-example.png" />';
  $image_markup = Markup::create($img);
  $decanter = Link::fromTextAndUrl('Decanter Lockup Component', Url::fromUri('https://decanter.stanford.edu/component/identity-lockup/'))->toString();

  $form['options_settings']['humsci_basic_lockup'] = [
    '#type' => 'fieldset',
    '#title' => t('Lockup Settings'),
    '#field_prefix' => "<p>$image_markup</p><p>More examples can be found at: $decanter</p>",
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['#tree'] = TRUE;

  $form['options_settings']['humsci_basic_lockup']['lockup']['option'] = [
    '#type' => 'select',
    '#title' => t('Lockup Options'),
    '#options' => [
      'default' => '- Default -',
      'a' => t('Option A'),
      'b' => t('Option B'),
      'c' => t('Option C'),
      'd' => t('Option D'),
      'e' => t('Option E'),
      'f' => t('Option F'),
      'g' => t('Option G'),
      'h' => t('Option H'),
      'i' => t('Option I'),
      'j' => t('Option J'),
      'k' => t('Option K'),
      'l' => t('Option L'),
      'm' => t('Option M'),
      'n' => t('Option N'),
      'o' => t('Option O'),
      'p' => t('Option P'),
      'q' => t('Option Q'),
      'r' => t('Option R'),
      's' => t('Option S'),
      't' => t('Option T'),
    ],
    '#default_value' => theme_get_setting('lockup.option'),
    '#description' => t("Layout options."),
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['line1'] = [
    '#type' => 'textfield',
    '#title' => t('Line 1'),
    '#default_value' => theme_get_setting('lockup.line1'),
    '#description' => t("Site title line."),
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['line2'] = [
    '#type' => 'textfield',
    '#title' => t('Line 2'),
    '#default_value' => theme_get_setting('lockup.line2'),
    '#description' => t("Secondary title line."),
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['line3'] = [
    '#type' => 'textfield',
    '#title' => t('Line 3'),
    '#default_value' => theme_get_setting('lockup.line3'),
    '#description' => t("Tertiary title line."),
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['line4'] = [
    '#type' => 'textfield',
    '#title' => t('Line 4'),
    '#default_value' => theme_get_setting('lockup.line4'),
    '#description' => t("Organization name."),
  ];

  $form['options_settings']['humsci_basic_lockup']['lockup']['line5'] = [
    '#type' => 'textfield',
    '#title' => t('Line 5'),
    '#default_value' => theme_get_setting('lockup.line5'),
    '#description' => t("Last line full width option."),
  ];

  // Global Footer
  $form['options_settings']['humsci_basic_global_footer'] = [
    '#type' => 'fieldset',
    '#title' => t('Global Footer Settings'),
  ];

  $form['options_settings']['humsci_basic_global_footer']['global_footer_variant_classname'] = [
    '#type' => 'select',
    '#title' => t('Global Footer Variant'),
    '#options' => [
      'default' => '- Default -',
      'bright' => t('Bright'),
      'dark' => t('Dark')
    ],
    '#default_value' => theme_get_setting('global_footer_variant_classname'),
  ];

  // Animation Enhancement
  $form['options_settings']['humsci_basic_animation_enhancement'] = [
    '#type' => 'fieldset',
    '#title' => t('Animation Enhancements'),
  ];

  $form['options_settings']['humsci_basic_animation_enhancement']['animation_toggle'] = [
    '#type' => 'checkbox',
    '#title' => t('Use animation enhancements'),
    '#default_value' => theme_get_setting('animation_toggle'),
    '#description' => t('This enables/disables animations and can be useful to prevent users from experiencing distraction or nausea from animated content. This also provides a method for meeting <a href="https://www.w3.org/WAI/WCAG21/Understanding/animation-from-interactions.html">WACG 2.1 Level AAA success criterion</a> if desired or required.'),
  ];

  // Experimental Features
  $form['options_settings']['humsci_basic_experimental_feature'] = [
    '#type' => 'fieldset',
    '#title' => t('Experimental Features'),
  ];

  $form['options_settings']['humsci_basic_experimental_feature']['experimental_toggle'] = [
    '#type' => 'checkbox',
    '#title' => t('Use experimental features'),
    '#default_value' => theme_get_setting('experimental_toggle'),
    '#description' => t('This enables/disables experimental features, please use with caution. The following features are implemented: There are no experimental features implemented at the moment.'),
  ];

  // Mega Menu Hides Standard Menu on Desktop
  $form['options_settings']['use_megamenu'] = [
    '#type' => 'fieldset',
    '#title' => t('Megamenu Settings'),
  ];

  $form['options_settings']['use_megamenu']['megamenu_toggle'] = [
    '#type' => 'checkbox',
    '#title' => t('Use megamenu on desktop'),
    '#default_value' => theme_get_setting('megamenu_toggle'),
    '#description' => t('This hides the standard menu on desktop to allow mega menu to display. The standard menu will still be enabled on mobile.'),
  ];

}
