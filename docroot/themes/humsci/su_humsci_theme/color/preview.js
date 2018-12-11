
(function ($, Drupal, drupalSettings) {
  Drupal.color = {
    logoChanged: false,
    callback: function callback(context, settings, $form) {
      console.log('here');
      if (!this.logoChanged) {
        $('.color-preview .color-preview-header img').attr('src', drupalSettings.color.logo);
        $('.color-preview .color-preview-footer img').attr('src', drupalSettings.color.footerLogo);
        this.logoChanged = true;
      }

      if (drupalSettings.color.logo === null) {
        $('div').remove('.color-preview-logo');
      }

      var $colorPreview = $form.find('.color-preview');
      var $colorPalette = $form.find('.js-color-palette');

      $colorPreview.find('.color-preview-brand-bar').css('background-color', $colorPalette.find('input[name="palette[branding]"]').val());
      $colorPreview.find('.color-preview-header').css('background-color', $colorPalette.find('input[name="palette[header]"]').val());
      $colorPreview.find('.color-preview-main').css('backgroundColor', $colorPalette.find('input[name="palette[main]"]').val());
      $colorPreview.find('.color-preview-footer').css('background-color', $colorPalette.find('input[name="palette[footer]"]').val());
      $colorPreview.find('.color-preview a').css('color', $colorPalette.find('input[name="palette[link]"]').val());

      // var gradientStart = $colorPalette.find('input[name="palette[top]"]').val();
      // var gradientEnd = $colorPalette.find('input[name="palette[bottom]"]').val();

      // $colorPreview.find('.color-preview-header').attr('style', 'background-color: ' + gradientStart + '; background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(' + gradientStart + '), to(' + gradientEnd + ')); background-image: -moz-linear-gradient(-90deg, ' + gradientStart + ', ' + gradientEnd + ');');
    }
  };
})(jQuery, Drupal, drupalSettings);
