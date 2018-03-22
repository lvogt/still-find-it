function setupDropdownLists()
{
  var lists = document.getElementsByClassName("dropdown-check-list");
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
        items.classList.add('visible');
        items.style.display = "block";
        helperOpen = lists[i].id;

        switch (evt.target.parentNode.id)
        {
          case "laue-list": updateLaueCkbox(); break;
          case "class-list": updateClassCkbox(); break;
          case "system-list": updateSystemCkbox(); break;
        }
      }

      items.onblur = function(evt)
      {
        items.classList.remove('visible');
      }
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
