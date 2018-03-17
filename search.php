<?php
  session_start();

  require_once("classes/database.inc.php");
  require_once("classes/CICSDSearch.inc.php");

  $columns = (isset($_POST["columns"]) ? $_POST["columns"] : "default");
  $invalidateCache = isset($_POST["nocache"]);
  unset($_POST["columns"]);
  unset($_POST["nocache"]);

  $search = new CICSDSearch($_POST, $columns, $invalidateCache);

  print json_encode($search->results);

  $mysqli->close();
