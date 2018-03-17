<?php
  session_start();

  require_once("classes/database.inc.php");
  require_once("classes/CFullEntry.inc.php");

  if (isset($_GET["cc"]))
    $entry = new CFullEntry($_GET["cc"], "cc");
  elseif (isset($_GET["id"]))
    $entry = new CFullEntry($_GET["id"]);
  else
    $entry = new CFullEntry();

  $type = (isset($_GET["type"]) ? strtolower($_GET["type"]) : "html");

  switch ($type)
  {
    case "cif": $entry->getCIF(); break;
    case "jsmol": $entry->displayJSmol(); break;
    case "html": $entry->display(); break;
    default: $entry->display();
  }

  $mysqli->close();
?>
