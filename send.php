<?php

$session = session_start();

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
    "<!DOCTYPE html\n".
    "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
    "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml-strict.dtd\">\n".
    "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n".
    "    <head>\n".
    "        <title>Feudal Lords</title>\n".
    "    </head>\n".
    "    <body>\n";

if ($session === true)
{
    $session = isset($_SESSION['user_id']);
}

if ($session !== true)
{
    echo "  <div>\n".
         "    Please first log in.\n".
         "  </div>\n".
         " </body>\n".
         "</html>\n";

    exit();
}

require_once("defines.inc.php");
require_once("database.inc.php");

if ($mysql_connection != false)
{
    $building = mysqli_query($mysql_connection, "SELECT `building`".
                            "FROM `building`\n".
                            "WHERE `user_id`=".$_SESSION['user_id']." AND\n".
                            "`building`='".ENUM_BUILDING_MARKET."'");
}
//first check if the user has a market
if ($building != false)
{
    if (mysqli_fetch_assoc($building) != true)
    {
        exit();
    }

    mysqli_free_result($building);
}


echo "<div>\n".
     "  <a href=\"overview.php\">Overview</a>\n".
     "  <hr />\n".
     "</div>\n";


if (isset($_POST['quantity']) !== true ||
    isset($_POST['resource']) !== true ||
    isset($_POST['receiver']) !== true)
{
    echo "<form action=\"send.php\" method=\"post\">\n".
         "  <input name=\"quantity\" type=\"text\" size=\"7\" maxlength=\"7\" /> Quantity<br />\n".
         "      <select name=\"resource\" size=\"1\">\n".
         "          <option value=\"food\">Food</option>\n".
         "          <option value=\"wood\">Wood</option>\n".
         "          <option value=\"stone\">Sone</option>\n".
         "          <option value=\"coal\">Coal</option>\n".
         "          <option value=\"iron\">Iron</option>\n".
         "          <option value=\"gold\">Gold</option>\n".
         "      </select> Resource<br />\n".
         "  <input name=\"receiver\" type=\"text\" size=\"20\" maxlength=\"40\" /> Receiver<br />\n".
         "  <input type=\"submit\" value=\"Send\" /><br />\n".
         "</form>\n";
}
else
{
    require_once("game.inc.php");

    $result = sendResources($_SESSION['user_id'],
                            $_POST['quantity'],
                            $_POST['resource'],
                            $_POST['receiver'],
                            $_SESSION['user_name']);

    switch ($result)
    {
    case 0:
        echo "<p>\n".
             "   Successfully sent.\n".
             "</p>\n";
        break;

    case -4:
        echo "<p>\n".
             "    Receiver does not exist.\n".
             "</p>\n";
        break;

    case -6:
        echo "<p>\n".
             "  Not enough quantity.\n".
             "</p>\n";
        break;

    default:
        echo "<p>\n".
             "   Error.\n".
             "</p>\n";
        break;
    }
}

echo "    </body>\n".
     "</html>\n".
     "\n";



?>
