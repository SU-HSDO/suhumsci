(function ($) {
  $(document).ready(function() {
    //Open first brick if only one brick.
    var $editButtons = $('div.ief-entity-operations input.js-form-submit[value="Edit"]');
    var editButtonCount = $editButtons.length;
    if (editButtonCount != null && editButtonCount == 1) {
      $editButtons.eq(0).once().mousedown();
    }
  });
}(jQuery));
