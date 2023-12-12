function closeDetails() {
  // Close Revision Information Details element by default.
  if (document.getElementById('edit-revision-information')) {
    document.getElementById('edit-revision-information').removeAttribute('open');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  closeDetails(document);
});
