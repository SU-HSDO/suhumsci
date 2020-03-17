// retrieve table column headings
let columnHeaders = document.querySelectorAll('.Cell');

// retrieve all rows
let tableRows = document.querySelectorAll('.hb-table-row');

// retrieve all instances of hb-table-row__heading
let tableRowHeaders = document.querySelectorAll('.hb-table-row__heading');

// For each row in the table
for (let x = 0; x < tableRows.length; x +=4) {
  // For each cell in the row populate the table header text
  // that will display on mobile screen sizes.
   for (let y = 0; y < columnHeaders.length; y += 1) {
     tableRowHeaders[x + y].innerHTML = columnHeaders[y].innerHTML;
   }
}
