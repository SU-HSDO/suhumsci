// account for different ways in which a table heading may be declared
const div = 'div.hb-table-pattern__header > div.hb-table-pattern__row > div';
const span = 'div.hb-table-pattern__header > div.hb-table-pattern__row > span';
const paragraph = 'div.hb-table-pattern__header > div.hb-table-pattern__row > p';

// retrieve table column headings
const columnHeaders = document.querySelectorAll(`${div}, ${span}, ${paragraph}`);

// retrieve all rows
const tableRows = document.querySelectorAll('.hb-table-row');

if (tableRows) {
  // For each row in the table
  for (let i = 0; i < tableRows.length; i += 1) {
    // find the row headers in each cell
    const tableRowHeaders = tableRows[i].querySelectorAll('.hb-table-row__heading');

    // we need h to step through columnHeaders and get the correct heading text
    for (let h = 0; h < tableRowHeaders.length; h += 1) {
      tableRowHeaders[h].innerHTML = columnHeaders[h].innerHTML;
    }
  }
}
