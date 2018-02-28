function chemBeautify()
{
  var eq = document.getElementsByClassName("ce");
  for (var i = 0; i < eq.length; i++)
  {
    var tmp = eq[i].innerHTML.replace(/([0-9.]+)/g, "<sub>$1</sub>");
    eq[i].innerHTML = tmp.replace(/\s*/g, "");
  }
}

function latticeBeautify()
{
  var lat = document.getElementsByClassName("lattice");
  for (var i = 0; i < lat.length; i++)
  {
    var tmp = lat[i].innerHTML.split(" ");
    var str = "a&nbsp;=&nbsp;" + tmp[0] + "; b&nbsp;=&nbsp;" + tmp[1] + "; c&nbsp;=&nbsp;" + tmp[2] + " &#8491;<br />";
    str += "&alpha;&nbsp;=&nbsp;" + tmp[3] + "; &beta;&nbsp;=&nbsp;" + tmp[4] + "; &gamma;&nbsp;=&nbsp;" + tmp[5] + "&deg;"
    lat[i].innerHTML = str;
  }
}

function localDate()
{
  var dates = document.getElementsByClassName("date");
  for (var i = 0; i < dates.length; i++)
  {
    var dateParts = dates[i].innerHTML.split("/");
    var date = new Date(dateParts[0], (dateParts[1] - 1), dateParts[2]);
    dates[i].innerHTML = date.toLocaleDateString(navigator.language, {year: 'numeric', month: 'long', day: '2-digit'});
  }
}

function authorBeautify()
{
  var author = document.getElementsByClassName("author");
  for (var i = 0; i < author.length; i++)
  {
    author[i].innerHTML = author[i].innerHTML.split(";").join("; ");
  }
}

window.onload = function()
{
  chemBeautify();
  localDate();
  latticeBeautify();
  authorBeautify();
}
