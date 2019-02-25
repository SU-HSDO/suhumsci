
(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {
      // Show the individual color selectors if the user is User 1.
      if (drupalSettings.color.userId == 1) {
        $('.color-placeholder, .color-palette').show();
      }

      // Change the logo to be the real one.
      if (!this.logoChanged) {
        $('.color-preview .color-preview-header img').attr('src', drupalSettings.color.logo);
        $('.color-preview .color-preview-global-footer img').attr('src', drupalSettings.color.globalFooterLogo);
        this.logoChanged = true;
      }

      // Remove the logo if the setting is toggled off.
      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      // Top Branding bar.
      $colorPreview.find('.color-preview-brand-bar').css('background-color', $colorPalette.find('input[name="palette[branding]"]').val());
      $colorPreview.find('.color-preview-brand-bar').css('color', $colorPalette.find('input[name="palette[brandingcolor]"]').val());

      // Header colors.
      $colorPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[header]"]').val());
      $colorPreview.find('.color-preview-header').css('color', $colorPalette.find('input[name="palette[headertext]"]').val());
      $colorPreview.find('.color-preview-header a').css('color', $colorPalette.find('input[name="palette[headerlink]"]').val());

      // Main content region.
      $colorPreview.find('.color-preview-main').css('background-color', $colorPalette.find('input[name="palette[main]"]').val());
      $colorPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[maintext]"]').val());
      $colorPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[mainlink]"]').val());

      // Pre-footer region.
      $colorPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $colorPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[footertext]"]').val());
      $colorPreview.find('.color-preview-footer a').css('color', $colorPalette.find('input[name="palette[footerlink]"]').val());

      // Stanford global footer bottom.
      $colorPreview.find('.color-preview-global-footer').css('background-color', $colorPalette.find('input[name="palette[globalfooter]"]').val());
      $colorPreview.find('.color-preview-global-footer, .color-preview-global-footer a').css('color', $colorPalette.find('input[name="palette[globalfooterlink]"]').val());

      // Decanter button links.
      $colorPreview.find('.color-preview-decanter-button').css('background-color', $colorPalette.find('input[name="palette[button]"]').val());
      $colorPreview.find('.color-preview-decanter-button').css('color', $colorPalette.find('input[name="palette[buttoncolor]"]').val());
    }
  };
})(jQuery, Drupal, drupalSettings);
