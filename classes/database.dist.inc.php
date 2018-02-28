<?

/*
 * Connection to the mysql database
 */

$mysqli = new mysqli("host",
                     "user",
                     "password",
                     "icsd");

if ($mysqli->connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli->connect_error;
  die;
}

/* change character set to utf8 */
if (!$mysqli->set_charset("utf8"))
{
  printf("Error loading character set utf8: %s\n", $mysqli->error);
  die;
}

?>
