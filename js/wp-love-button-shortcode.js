var table = document.getElementById("wplb-tags-loves");
var numberOfRows = 11;
var totalRowCount = table.rows.length;
var firstRow = table.rows[0].firstElementChild.tagName;
var hasHead = (firstRow === "th");
var tr = [];
var i, ii, j = (hasHead) ? 1 : 0;
var th = (hasHead ? table.rows[(0)].outerHTML : "");
var pageCount = Math.ceil(totalRowCount / numberOfRows);

if (pageCount > 1) {
    for (i = j, ii = 0; i < totalRowCount; i++, ii++)
        tr[ii] = table.rows[i].outerHTML;
    table.insertAdjacentHTML("afterend", "<div id='wplb-buttons'></div");
    wplb_change_page(1);
}

function wplb_change_page(p) {
    var rows = th,
        s = ((numberOfRows * p) - numberOfRows);
    for (i = s; i < (s + numberOfRows) && i < tr.length; i++)
        rows += tr[i];

    table.innerHTML = rows;
    document.getElementById("wplb-buttons").innerHTML = pageButtons(pageCount, p);
    document.getElementById("id" + p).setAttribute("class", "wplb-active");
}

function pageButtons(pCount, cur) {
    var prevDis = (cur == 1) ? "disabled" : "",
        nextDis = (cur == pCount) ? "disabled" : "",
        buttons = "<input type='button' class= 'wplb-button' value='&lt;&lt; Prev' onclick='wplb_change_page(" + (cur - 1) + ")' " + prevDis + ">";
    for (i = 1; i <= pCount; i++)
        buttons += "<input type='button' class= 'wplb-button' id='id" + i + "'value='" + i + "' onclick='wplb_change_page(" + i + ")'>";
    buttons += "<input type='button' class= 'wplb-button' value='Next &gt;&gt;' onclick='wplb_change_page(" + (cur + 1) + ")' " + nextDis + ">";
    return buttons;
}