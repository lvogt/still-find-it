var results;
var resInfo;
var lastSort = "coll_code";
var maxRows = 25;
var colString = "";
var hashArray = {};
var searchData;
var invalidateCache = false;
var dragSource;
var columns = [
  "idnum",
  "coll_code",
  "mpy",
  "sgr",
  "sum_form",
  "unit_cell_text",
  "c_vol",
  "authors_text",
  "au_title"
];
var allColumns = {
  idnum: "id",
  coll_code: "CSD",
  mpy: "Year",
  sgr: "Space group",
  sum_form: "Sum formula",
  struct_form: "Structured formula",
  unit_cell_text: "Unit cell",
  c_vol: "Volume",
  cdiva: "c/a",
  dens_text: "Density",
  authors_text: "Authors",
  au_title: "Publication",
  min_name: "Mineral",
  r_text: "R",
  temperature: "Temperature",
  pressure: "Pressure"
};

window.onload = init;
window.onhashchange = hashToSearch;

function init()
{
  var nowarpCols = [];
  var smallTxtCols = [];
  var colCount = 0;

  try
  {
    var cols = getCookie("columns");
    if (cols != "")
    {
      cols = JSON.parse(cols);
      columns = cols;
    }
  }
  catch(error) { console.log(error.name + ": " + error.message); }

  var newMaxRows = parseInt(getCookie("maxRows"));
  if (!isNaN(newMaxRows) && isFinite(newMaxRows) && (newMaxRows != 0))
      maxRows = newMaxRows;

  columns.forEach((item) => {
    if (item == "idnum")
      return;

    if (/^authors_text|au_title$/.test(item))
      smallTxtCols.push(colCount);

    colCount++;
  });

  fillColumnList();

  //bind onclick events
  document.getElementById("btnSubmit").onclick= formToHash;
  document.getElementById("btnSettings").onclick = displaySettings;
  document.getElementById("btnReset").onclick = function() { document.getElementById("searchForm").reset(); }
  document.getElementById("resultSettings").onclick = closeSettings;
  document.getElementById("xSettings").onclick = closeSettings;

  document.getElementById("searchForm").onkeyup = (event) => { if (event.key == "Enter") formToHash(); }

  hashToSearch();
}

function fillColumnList()
{
  //settings: fill list with possible columns
  document.getElementById("formCols").innerHTML = "";

  var html = "<ul id=\"columnList\">";

  columns.forEach((item) => {
    if (item == "idnum")
      return;

    html += '<li class="dragList" draggable="true" ondragenter="listDragEnter(event)" ondragstart="listDragStart(event)"><input type="checkbox" id="' + item + '_s" name="' + item + '_s" />'
         +  '<label for="' + item + '_s">' + allColumns[item] + '</label></li>';
  });

  for (var key in allColumns)
  {
    if ((key == "idnum") || (columns.indexOf(key) != -1))
      continue;
    html += '<li class="dragList" draggable="true" ondragenter="listDragEnter(event)" ondragstart="listDragStart(event)"><input type="checkbox" id="' + key + '_s" name="' + key + '_s" />'
         +  '<label for="' + key + '_s">' + allColumns[key] + '</label></li>';
  }
  html += "</ul>";
  document.getElementById("formCols").innerHTML = html;
}

function isbefore(a, b)
{
  if (a.parentNode != b.parentNode)
    return false;

  for (var cur = a; cur; cur = cur.previousSibling)
    if (cur === b)
      return true;

  return false;
}

function listDragEnter(event)
{
  if (isbefore(dragSource, event.target))
    event.target.parentNode.insertBefore(dragSource, event.target);
  else
    event.target.parentNode.insertBefore(dragSource, event.target.nextSibling);
}

function listDragStart(event)
{
  console.log("dragStart");
  event.dataTransfer.setData("text/plain", null);
  dragSource = event.target;
  event.dataTransfer.effectAllowed = 'move';
}

function displaySettings()
{
  document.getElementById("formCols").reset();
  columns.forEach((item) => {
    if (item == "idnum")
      return;

    document.getElementById(item + "_s").checked = true;
  });
  document.getElementById("rows").value = maxRows;

  document.getElementById("resultSettings").style.display = "block";
}

function closeSettings(event)
{
  if ((event.target.className == "modal") || (event.target.className == "close"))
  {
    document.getElementById("resultSettings").style.display = "none";
    document.getElementById("coll_code_s").checked = true;

    var newMaxRows = parseInt(document.getElementById("rows").value);
    if (!isNaN(newMaxRows) && isFinite(newMaxRows) && (newMaxRows != maxRows))
    {
      maxRows = newMaxRows;
      setCookie("maxRows", maxRows, 360);
      updateTable(maxRows, 0);
    }

    var cols = new FormData(document.getElementById("formCols"));
    var newCols = ["idnum"];

    for (var pair of cols.entries())
      newCols.push(pair[0].slice(0,-2));

    var colsChanged = false;
    if (newCols.length == columns.length)
    {
      for (let i = 0; i < newCols.length; i++)
        if (newCols[i] != columns[i])
        {
          colsChanged = true;
          break;
        }
    }
    else
      colsChanged = true;

    if (colsChanged)
    {
      columns = newCols;
      //location.hash = "";
      invalidateCache = true;
      setCookie("columns", JSON.stringify(columns), 360);
      fillColumnList();
      hashToSearch();
    }
  }
}

function setCookie(name, value, days)
{
  var expires = "";
  if (days)
  {
    var date = new Date();
    date.setTime(date.getTime() + (days*24*60*60*1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function getCookie(name)
{
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++)
  {
    var c = ca[i];
    while (c.charAt(0) == ' ')
      c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0)
      return c.substring(nameEQ.length, c.length);
  }
  return "";
}

function eraseCookie(name)
{
  document.cookie = name + "=; Max-Age=-99999999;";
}

function hashToSearch()
{
  if (window.location.hash == "")
    return;

  //check for get values
  hashArray = {};
  window.location.hash.replace(/[?#]?([^=&]+)=?([^&]*)?/gi,
    function(m, key, value) { hashArray[decodeURI(key)] = (value !== undefined ? decodeURI(value) : ''); });

  var empty = true;
  delete searchData;
  searchData = new FormData();
  document.getElementById("searchForm").reset();

  try
  {
    for (var key in hashArray)
    {
      if (hashArray[key] != "")
      {
        searchData.append(key, hashArray[key]);
        document.getElementById(key).value = hashArray[key];
        empty = false;
      }
      else
        document.getElementById(key).value = "";
    }
  }
  catch(error) { console.log(error.name + ": " + error.message); }

  searchData.append("columns", JSON.stringify(columns));
  if (invalidateCache)
    searchData.append("nocache", "");

  if (!empty)
    doSearch();
}

function formToHash()
{
  var data = new FormData(document.getElementById("searchForm"));
  var hashString = "";
  for (var pair of data.entries())
  {
    if (pair[1] != "")
      hashString += "&" + encodeURI(pair[0]) + '=' + encodeURI(pair[1]);
  }
  window.location.hash = hashString.substr(1);
}

function doSearch()
{
  lastSort = "coll_code";
  document.getElementById("loaderDiv").classList.remove("hidden");

  fetch('search.php', {method: 'POST', credentials: 'same-origin', body: searchData}).then(
    response => results = response.text()
  ).then(
    txt => {
      try
      {
        results = JSON.parse(txt);
        document.getElementById("loaderDiv").classList.add("hidden");
        resInfo = results.pop();
        invalidateCache = false;

        if (resInfo.success)
          updateTable(maxRows, 0);
        else
          noResults();
      }
      catch (error)
      {
        console.log(error.name + ": " + error.message);
        console.log("received response (saved in lastResponse):");
        console.log(txt);
        eval("lastResponse = txt");
        document.getElementById("loaderDiv").classList.add("hidden");
        document.getElementById("resultTable").innerHTML = "";
        document.getElementById("tableNav").innerHTML = "An unkown error occured. Please check the console.";
      }
    }
  )
}

function noResults()
{
  document.getElementById("resultTable").innerHTML = "";

  var html = "";

  if (resInfo.rows == 0)
    html = "No results found. :-(";
  else
  {
    html = "Error: " + resInfo.error;
    if (resInfo.rows > resInfo.limit)
      html += " Found " + resInfo.rows + " entries (Limit: " + resInfo.limit
          +  "). Try to narrow your search.";
  }

  document.getElementById("tableNav").innerHTML = html;
}

function updateTable(maxRows, start, sortBy)
{
  if ((typeof(results) === "undefined") || (results.length == 0))
  {
    document.getElementById("resultTable").innerHTML = "";
    return;
  }

  sortBy = (typeof sortBy !== 'undefined') ?  sortBy : "";

  if (sortBy != "")
  {
    sortResults(sortBy);
    start = 0;
  }

  var html = "<thead><tr><th></th>";

  columns.forEach(function(item) {
    if (item == "idnum")
      return;

    html += "<th id='" + item + "_head' onclick=\"updateTable(" + maxRows + ", "
          + start + ", '" + item + "')\""
          + (/^authors_text|au_title$/.test(item) ? " class=\"small-txt\"" : "")
          + ">" + allColumns[item] + "</th>";
  });

  html += "</tr></thead><tbody>";

  for (var i = start; (i < start+maxRows) && (i < results.length); i++)
  {
    if (i % 2)
      html += "<tr class=\"odd\">";
    else
      html += "<tr class=\"even\">";

    html += "<td data-label=\"\" class=\"ckbCol\"><input type=\"checkbox\" id=\"ckb-" + i + "\" />";

    columns.forEach(function(item) {
      if (item == "idnum")
        return;

      if (item == "coll_code")
        html += "<td data-label=\"" + allColumns[item] + "\"><a href=\"entry.php?id=" + results[i]["idnum"]
              + "\" target=\"icsd_detail\">" + results[i][item] + "</a></td>";
      else
      {
        if (/^authors_text|au_title$/.test(item))
          html += "<td data-label=\"" + allColumns[item] + "\" class=\"small-txt\" title=\"" + results[i][item] + "\">" + results[i][item] + "</td>";
        else
          html += "<td data-label=\"" + allColumns[item] + "\">" + (results[i][item] != null ? results[i][item] : "")  + "</td>";
      }
    });

    html += "</tr>";
  }

  html += "</tbody>";

  document.getElementById("resultTable").innerHTML = html;

  //need to wait after DOM has been rebuild with this!
  if (lastSort[0] === "-")
    document.getElementById(lastSort.substring(1) + "_head").classList.add("sort-up");
  else
    document.getElementById(lastSort + "_head").classList.add("sort-down");

  //navigation
  if (results.length > maxRows)
  {
    var prev = start - maxRows;
    if (prev < 0)
      prev = 0;

    var last = results.length - (results.length % maxRows);
    if (last == results.length)
      last -= maxRows;

    var next = start + maxRows;
    if (next >= results.length)
      next = last;

    var pageCount = Math.ceil(results.length/maxRows);
    var dropDown = '<select class="button" id="selID" onchange="updateTable(' + maxRows + ', (this.value*maxRows))">';
    for (var i = 0;i < pageCount; i++)
      dropDown += '<option value="' + i + '">' + (maxRows*i+1) + '+</option>';
    dropDown += "</select>";

    html = "<a class=\"button\" onclick=\"updateTable(" + maxRows + ", 0)\">&lt;&lt;</a>&nbsp;"
         + "<a class=\"button\" onclick=\"updateTable(" + maxRows + ", " + prev + ")\">&lt;</a>&nbsp;&nbsp;"
         + dropDown + "&nbsp;&nbsp;"
         + "<a class=\"button\" onclick=\"updateTable(" + maxRows + ", " + next + ")\">&gt;</a>&nbsp;"
         + "<a class=\"button\" onclick=\"updateTable(" + maxRows + ", "
         + last + ")\">&gt;&gt;</a>&nbsp;&nbsp;"
         + "<span class=\"resCount\">Results: (" + (start+1) + " - " + (start+maxRows > results.length ? results.length : (start+maxRows)) + ") of " + results.length + "</span>";
  }
  else
    html = "|&lt;&lt;&nbsp;&nbsp;&lt;&nbsp;&nbsp;&gt;&nbsp;&nbsp;&gt;&gt;|&nbsp;&nbsp;Results: " + results.length;

  html += " <span class=\"info\">exec. time: " + (resInfo.fetchTime*1000).toFixed(3) + " ms";
  if (resInfo.cached)
    html += " (cached search)";
  html += "</span>";

  document.getElementById("tableNav").innerHTML = html;
  if (results.length > maxRows)
    document.getElementById("selID").value = Math.trunc(start / maxRows);
}

function dynamicSort(property, isNum = false)
{
  var sortOrder = 1;
  var numcomp = isNum;
  if(property[0] === "-")
  {
    sortOrder = -1;
    property = property.substr(1);
  }

  return function (a,b)
  {
    var result;
    if (!numcomp)
      result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
    else
    {
      aF = parseFloat(a[property]);
      bF = parseFloat(b[property]);
      result = (aF < bF) ? -1 : (aF > bF) ? 1 : 0;
    }
    return result * sortOrder;
  }
}

function sortResults(sortBy)
{
  var numeric = false;
  if ((sortBy == "coll_code") || (sortBy == "c_vol") || (sortBy == "pressure")
      || (sortBy == "temperature") || (sortBy == "r_text") || (sortBy == "dens_text")
      || (sortBy == "cdiva"))
    numeric = true;

  if (lastSort == sortBy)
    sortBy = "-" + sortBy;

  lastSort = sortBy;

  results.sort(dynamicSort(sortBy, numeric));
}
