function setupDropdownLists()
{
  var lists = document.getElementsByClassName("dropdown");
  for (let i = 0; i < lists.length; i++)
  {
    lists[i].getElementsByClassName('anchor')[0].onclick = function(evt)
    {
      var items = evt.target.parentNode.getElementsByClassName('dropdown-items')[0];
      if (items.classList.contains('visible'))
      {
        closeHelper();
      }
      else
      {
        closeHelper();
        items.classList.add('visible');
        items.style.display = "block";
        helperOpen = lists[i].id;

        switch (evt.target.parentNode.id)
        {
          case "laue-list": updateLaueCkbox(); break;
          case "class-list": updateClassCkbox(); break;
          case "system-list": updateSystemCkbox(); break;
          case "tests-list": updateTestsCkbox(); break;
          case "pol_cent-list": updatePolCentCkbox(); break;
        }
      }

      items.onblur = function(evt)
      {
        items.classList.remove('visible');
      }
    }
  }

  var elements = document.getElementById("exclude-pse").getElementsByTagName("li");
  for (let i = 0; i < elements.length; i++)
  {
    elements[i].onclick = function(evt)
    {
      var el = evt.target.innerHTML;
      switch (el)
      {
        case "TR1": el = "SCP"; break;
        case "TR2": el = "YP"; break;
        case "TR3": el = "LAP"; break;
        case "TR4": el = "ACP"; break;
        case "P1": el = "HP"; break;
        case "P2": el = "LIP"; break;
        case "P3": el = "NAP"; break;
        case "P4": el = "KP"; break;
        case "P5": el = "RBP"; break;
        case "P6": el = "CSP"; break;
        case "P7": el = "FRP"; break;
        case "Lan": el = "LAN"; break;
        case "Act": el = "ACT"; break;
        case "\u2160": el = "ALK"; break;
        case "\u2161": el = "ALE"; break;
        case "\u2162": el = "SCG"; break;
        case "\u2163": el = "TIG"; break;
        case "\u2164": el = "VG"; break;
        case "\u2165": el = "CRG"; break;
        case "\u2166": el = "MNG"; break;
        case "\u2167": el = "FEG"; break;
        case "\u2168": el = "COG"; break;
        case "\u2169": el = "NIG"; break;
        case "\u216A": el = "CUG"; break;
        case "\u216B": el = "ZNG"; break;
        case "\u2169\u2162": el = "BG"; break;
        case "\u2169\u2163": el = "TET"; break;
        case "\u2169\u2164": el = "PNC"; break;
        case "\u2169\u2165": el = "CHA"; break;
        case "\u2169\u2166": el = "HAL"; break;
        case "\u2169\u2167": el = "NGS"; break;
        default: break;
      }
      var val = document.getElementById("exclude");

      if (new RegExp(el + "\\s").test(val.value))
        return;

      val.value += el + " ";
    }
  }

  helperOpen = null;
  window.onclick = handleGlobalClicks;
}

function closeHelper()
{
  if (helperOpen == null)
    return;

  var items = document.getElementById(helperOpen).getElementsByClassName('dropdown-items')[0];
  items.classList.remove('visible');
  items.style.display = "none";

  switch (helperOpen)
  {
    case "laue-list": updateLaueInput(); break;
    case "class-list": updateClassInput(); break;
    case "system-list": updateSystemInput(); break;
    case "tests-list": updateTestsInput(); break;
    case "pol_cent-list": updatePolCentInput(); break;
  }

  helperOpen = null;
}

function handleGlobalClicks(event)
{
  if ((helperOpen != null) && (event.target.closest("#" + helperOpen) == null))
    closeHelper();
}

function updateLaueCkbox()
{
  var matched = document.getElementById("laue").value.match(/([12346\/m-]+)/g);
  var ckb = document.getElementsByClassName("laue-ckb");
  for (let i = 0; i < ckb.length; i++)
    ckb[i].checked = !((matched == null) || (matched.indexOf(ckb[i].value) === -1));
}

function updateLaueInput()
{
  var ckb = document.getElementsByClassName("laue-ckb");
  var str = "";
  for (let i = 0; i < ckb.length; i++)
  {
    if (ckb[i].checked)
      str += ", " + ckb[i].value;
  }
  document.getElementById("laue").value = str.substr(2);
}

function updateClassCkbox()
{
  var matched = document.getElementById("class").value.match(/([12346\/m-]+)/g);
  var ckb = document.getElementsByClassName("class-ckb");
  for (let i = 0; i < ckb.length; i++)
    ckb[i].checked = !((matched == null) || (matched.indexOf(ckb[i].value) === -1));
}

function updateClassInput()
{
  var ckb = document.getElementsByClassName("class-ckb");
  var str = "";
  for (let i = 0; i < ckb.length; i++)
  {
    if (ckb[i].checked)
      str += ", " + ckb[i].value;
  }
  document.getElementById("class").value = str.substr(2);
}

function updateSystemCkbox()
{
  var matched = document.getElementById("system").value.match(/(tric|mono|ortho|tetra|trig|hex|cubic)/g);
  var ckb = document.getElementsByClassName("system-ckb");
  for (let i = 0; i < ckb.length; i++)
    ckb[i].checked = !((matched == null) || (matched.indexOf(ckb[i].value) === -1));
}

function updateSystemInput()
{
  var ckb = document.getElementsByClassName("system-ckb");
  var str = "";
  for (let i = 0; i < ckb.length; i++)
  {
    if (ckb[i].checked)
      str += ", " + ckb[i].value;
  }
  document.getElementById("system").value = str.substr(2);
}

function updateTestsCkbox()
{
  var matched = document.getElementById("tests").value.match(/([1-9][0-9])/g);
  var ckb = document.getElementsByClassName("tests-ckb");
  for (let i = 0; i < ckb.length; i++)
    ckb[i].checked = !((matched == null) || (matched.indexOf(ckb[i].value) === -1));
}

function updateTestsInput()
{
  var ckb = document.getElementsByClassName("tests-ckb");
  var str = "";
  for (let i = 0; i < ckb.length; i++)
  {
    if (ckb[i].checked)
      str += ", " + ckb[i].value;
  }
  document.getElementById("tests").value = str.substr(2);
}

function updatePolCentCkbox()
{
  var matched = document.getElementById("pol_cent").value.match(/(acen|pol|cent|achi)/g);
  var ckb = document.getElementsByClassName("pol_cent-ckb");
  for (let i = 0; i < ckb.length; i++)
    ckb[i].checked = !((matched == null) || (matched.indexOf(ckb[i].value) === -1));
}

function updatePolCentInput()
{
  var ckb = document.getElementsByClassName("pol_cent-ckb");
  var str = "";
  for (let i = 0; i < ckb.length; i++)
  {
    if (ckb[i].checked)
      str += ", " + ckb[i].value;
  }
  document.getElementById("pol_cent").value = str.substr(2);
}
