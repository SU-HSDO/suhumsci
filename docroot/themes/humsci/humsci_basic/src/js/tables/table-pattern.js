// account for different ways in which a table heading may be declared
let div = "div.hb-table-pattern__header > div.hb-table-pattern__row > div";
let span = "div.hb-table-pattern__header > div.hb-table-pattern__row > span";
let paragraph = "div.hb-table-pattern__header > div.hb-table-pattern__row > p";

// retrieve table column headings
const columnHeaders = document.querySelectorAll(`${div}, ${span}, ${paragraph}`);

// retrieve all rows
const tableRows = document.querySelectorAll('.hb-table-row');

// For each row in the table
tableRows.forEach((row) => {
  // find the row headers in each cell
  const tableRowHeaders = [...row.querySelectorAll('.hb-table-row__heading')];
  // we need i to step through columnHeaders and get the correct heading text
  let i = 0;

  tableRowHeaders.forEach((header) => {
    header.innerHTML = columnHeaders[i].innerHTML;
    i += 1;
  });
});
