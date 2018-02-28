<?
  session_start();

  header("Access-Control-Allow-Origin: *");

  require_once("classes/database.inc.php");
  require_once("classes/CICSDSearch.inc.php");

  //$getCount = (isset($_POST["getCount"]) ? $_POST["getCount"] == "true" : false);
  $columns = (isset($_POST["columns"]) ? $_POST["columns"] : "default");
  $invalidateCache = isset($_POST["nocache"]);
  unset($_POST["columns"]);
  unset($_POST["nocache"]);

  $search = new CICSDSearch($_POST, $columns,$invalidateCache);

  print json_encode($search->results);

  $mysqli->close();
?>
