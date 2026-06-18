(function (Drupal, once) {
  Drupal.behaviors.viewsTableHeaders = {
    attach(context) {
      const tables = once('views-table-headers', '.table', context);

      tables.forEach((table) => {
        // retrieve table column headings
        const columnHeaders = table.querySelectorAll('thead th');

        // retrieve all rows
        const tableRows = table.querySelectorAll('tbody tr');

        if (tableRows) {
          // only inject headings on mobile
          const mediaQuery = window.matchMedia('(max-width: 767px)');

          const injectHeadings = () => {
            // For each row in the table
            for (let i = 0; i < tableRows.length; i += 1) {
              // find the cells in each row
              const tableCells = tableRows[i].querySelectorAll('td');

              // we need h to step through columnHeaders and get the correct heading text
              for (let h = 0; h < tableCells.length; h += 1) {
                if (columnHeaders[h]) {
                  // avoid duplicate injection on resize
                  if (!tableCells[h].querySelector('.views-table__heading')) {
                    const heading = document.createElement('span');
                    heading.className = 'views-table__heading';
                    // hide the heading on the first column
                    if (h === 0) {
                      heading.classList.add('views-table__heading--hidden');
                    }
                    heading.innerHTML = columnHeaders[h].innerHTML;
                    tableCells[h].insertAdjacentElement('afterbegin', heading);
                  }
                }
              }
            }
          };

          // strip injected headings so the visible <thead> isn't duplicated
          const removeHeadings = () => {
            table
              .querySelectorAll('.views-table__heading')
              .forEach((heading) => heading.remove());
          };

          // inject on load if already mobile
          if (mediaQuery.matches) {
            injectHeadings();
          }

          // sync on viewport changes in both directions
          mediaQuery.addEventListener('change', (event) => {
            if (event.matches) {
              injectHeadings();
            } else {
              removeHeadings();
            }
          });
        }
      });
    },
  };
}(Drupal, once));
