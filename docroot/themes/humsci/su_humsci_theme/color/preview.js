
(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {
      if (drupalSettings.color.userId == 1) {
        $('.color-placeholder, .color-palette').show();
      }
      if (!this.logoChanged) {
        $('.color-preview .color-preview-header img').attr('src', drupalSettings.color.logo);
        $('.color-preview .color-preview-global-footer img').attr('src', drupalSettings.color.globalFooterLogo);
        this.logoChanged = true;
      }

      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');


      $colorPreview.find('.color-preview-brand-bar').css('background-color', $colorPalette.find('input[name="palette[branding]"]').val());
      $colorPreview.find('.color-preview-brand-bar').css('color', $colorPalette.find('input[name="palette[branding_color]"]').val());

      $colorPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[header]"]').val());
      $colorPreview.find('.color-preview-header').css('color', $colorPalette.find('input[name="palette[header_text]"]').val());
      $colorPreview.find('.color-preview-header a').css('color', $colorPalette.find('input[name="palette[header_link]"]').val());

      $colorPreview.find('.color-preview-main').css('background-color', $colorPalette.find('input[name="palette[main]"]').val());
      $colorPreview.find('.color-preview-main').css('color', $colorPalette.find('input[name="palette[main_text]"]').val());
      $colorPreview.find('.color-preview-main a').css('color', $colorPalette.find('input[name="palette[main_link]"]').val());

      $colorPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $colorPreview.find('.color-preview-footer').css('color', $colorPalette.find('input[name="palette[footer_text]"]').val());
      $colorPreview.find('.color-preview-footer a').css('color', $colorPalette.find('input[name="palette[footer_link]"]').val());

      $colorPreview.find('.color-preview-global-footer').css('background-color', $colorPalette.find('input[name="palette[global_footer]"]').val());
      $colorPreview.find('.color-preview-global-footer, .color-preview-global-footer a').css('color', $colorPalette.find('input[name="palette[global_footer_link]"]').val());

      $colorPreview.find('.color-preview-decanter-button').css('background-color', $colorPalette.find('input[name="palette[button]"]').val());
      $colorPreview.find('.color-preview-decanter-button').css('color', $colorPalette.find('input[name="palette[button_color]"]').val());

      // var gradientStart = $colorPalette.find('input[name="palette[top]"]').val();
      // var gradientEnd = $colorPalette.find('input[name="palette[bottom]"]').val();

      // $colorPreview.find('.color-preview-header').attr('style', 'background-color: ' + gradientStart + '; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(' + gradientStart + '), to(' + gradientEnd + ')); background-image: -moz-linear-gradient(-90deg, ' + gradientStart + ', ' + gradientEnd + ');');
    }
  };
})(jQuery, Drupal, drupalSettings);
