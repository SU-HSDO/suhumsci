if (jQuery.ui && jQuery.ui.autocomplete) {
  jQuery.ui.autocomplete.prototype._resizeMenu = function () {
    var ul = this.menu.element;
    ul.outerWidth(this.element.outerWidth());
  }
}
