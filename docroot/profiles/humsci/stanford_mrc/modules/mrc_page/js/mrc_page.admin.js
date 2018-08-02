jQuery(document).ready(function($) {
  //Confirmatioin on cancelling page changes.
  $('#edit-cancel').click(function(event){
    if(!confirm('Are you sure you want to cancel?')) {
      event.preventDefault();
    }
  });
});
