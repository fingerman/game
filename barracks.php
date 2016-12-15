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
    echo "<div>\n".
        "Please log in! \n".
        "</div>\n".
        "</body>\n".
        "</html>\n";

    exit();
}

echo "<div>\n".
    "<a href=\"overview.php\">Overview</a>\n".
    "<hr />\n".
    "</div>\n";


require_once("defines.inc.php");
require_once("database.inc.php");


$building = false;


if (isset($_POST['building']) !== true) {
    echo "<p>\n" .
        "  &nbsp;&nbsp;&nbsp;   Build Army Units: " .
        "</p>\n" .
        "  <form action=\"barracks.php\" method=\"post\">\n" .
        "<p>\n" .
        " Town:\n" .
        "</p>\n";

        echo "<input type=\"radio\" name=\"building\" value=\"" . ENUM_BUILDING_SOLDIER . "\" /> Soldier (needs: 1 Day &ndash; 1 Food &ndash; 0 Wood &ndash; none).<br />\n";
        echo "<input type=\"radio\" name=\"building\" value=\"" . ENUM_BUILDING_CAVALIER . "\" /> Cavalier (needs: 1 Day &ndash; 1 Food &ndash; 1 Wood &ndash; none).<br />\n";
        echo " <input type=\"submit\" value=\"Send\" /><br />\n".
        " </form>\n";

    }




else {
        require_once("game.inc.php");

        $result = insertNewBuilding($_SESSION['user_id'], $_POST['building']);

        switch ($result) {
            case 0:
                echo "<p>\n" .
                    "  Producing...\n" .
                    "</p>\n";
                break;

            case -6:
                echo "<p>\n" .
                    "  Not enough wood.\n" .
                    "</p>\n";
                break;

            case -7:
                echo "<p>\n" .
                    "  Not enough Stone.\n" .
                    "</p>\n";
                break;

            default:
                echo "<p>\n" .
                    "  Something went wrong.\n" .
                    "</p>\n";
                break;
        }
    }

echo "    </body>\n".
    "</html>\n".
    "\n";
