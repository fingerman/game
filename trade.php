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
    //chekc if the user has a market
    $building = mysqli_query($mysql_connection, "SELECT `building`".
                            "FROM `building`\n".
                            "WHERE `user_id`=".$_SESSION['user_id']." AND\n".
                            " `building`='".ENUM_BUILDING_MARKET."'");
}

if ($building != false)
{
    if (mysqli_fetch_assoc($building) != true)
    {
        exit();
    }

    mysqli_free_result($building);
}


echo "<div>\n".
     "   <a href=\"overview.php\">Overview</a>\n".
     "  <hr />\n".
     "</div>\n";


//if not set, show the form
if ((isset($_POST['get_amount']) !== true ||
     isset($_POST['get_type']) !== true ||
     isset($_POST['give_amount']) !== true ||
     isset($_POST['give_type']) !== true) &&
    (isset($_POST['action']) !== true))
{
    if ($mysql_connection != false)
    {
        $trades = mysqli_query($mysql_connection, "SELECT `id`,\n".
                              "`give_amount`,\n".
                              "`give_type`,\n".
                              "`get_amount`,\n".
                              "`get_type`,\n".
                              "`user_id`\n".
                              "FROM `trading`\n".
                              "ORDER BY `time` DESC\n");
    }

    if ($trades != false)
    {
        $result = array();

        while ($temp = mysqli_fetch_assoc($trades))
        {
            $result[] = $temp;
        }

        mysqli_free_result($trades);
        $trades = $result;
    }

    if (is_array($trades) === true)
    {
        echo "<form action=\"trade.php\" method=\"post\">\n".
             " <table border=\"1\">\n".
             "   <tr>\n".
             "      <th>Offer</th>\n".
             "      <th>Need</th>\n".
             "      <th>Action</th>\n".
             "    </tr>\n";

        foreach ($trades as $trade)
        {
            echo " <tr>\n".
                 "  <td>".$trade['give_amount']." ".translateEnumResourceToDisplayText($trade['give_type'])."</td>\n".
                 "  <td>".$trade['get_amount']." ".translateEnumResourceToDisplayText($trade['get_type'])."</td>\n";

            //if the user id is the same -> show Delete button
            if ($trade['user_id'] == $_SESSION['user_id'])
            {
                echo " <td><input type=\"radio\" name=\"action\" value=\"".$trade['id']."\" /> Delete</td>\n";
            }
            //if not -> show Accept button
            else
            {
                echo "<td><input type=\"radio\" name=\"action\" value=\"".$trade['id']."\" /> Accept</td>\n";
            }

            echo "</tr>\n";
        }

        echo "   <tr>\n".
             "   <td colspan=\"3\" align=\"right\"><input type=\"submit\" value=\"Send\" /></td>\n".
             "  </tr>\n".
             " </table>\n".
             "</form>\n";
    }

    echo "<p>\n" .
        "\n" .
         "Search:\n".
         "</p>\n".
         "<form action=\"trade.php\" method=\"post\">\n".
         "<input name=\"get_amount\" type=\"text\" size=\"7\" maxlength=\"7\" /> Quantity<br />\n".
         "<select name=\"get_type\" size=\"1\">\n".
         " <option value=\"".ENUM_RESOURCE_FOOD."\">Food</option>\n".
         " <option value=\"".ENUM_RESOURCE_WOOD."\">Wood</option>\n".
         " <option value=\"".ENUM_RESOURCE_STONE."\">Stone</option>\n".
         " <option value=\"".ENUM_RESOURCE_COAL."\">Coal</option>\n".
         " <option value=\"".ENUM_RESOURCE_IRON."\">Iron</option>\n".
         " <option value=\"".ENUM_RESOURCE_GOLD."\">Gold</option>\n".
         "</select> Resource<br />\n".
         "<p>\n".
         "   Offer:\n".
         "</p>\n".
         " <input name=\"give_amount\" type=\"text\" size=\"7\" maxlength=\"7\" /> Quantity<br />\n".
         " <select name=\"give_type\" size=\"1\">\n".
         "  <option value=\"".ENUM_RESOURCE_FOOD."\">Food</option>\n".
         "  <option value=\"".ENUM_RESOURCE_WOOD."\">Wood</option>\n".
         "  <option value=\"".ENUM_RESOURCE_STONE."\">Stone</option>\n".
         "  <option value=\"".ENUM_RESOURCE_COAL."\">Coal</option>\n".
         "  <option value=\"".ENUM_RESOURCE_IRON."\">Iron</option>\n".
         "  <option value=\"".ENUM_RESOURCE_GOLD."\">Gold</option>\n".
         " </select> Resource<br />\n".
         " <input type=\"submit\" value=\"Send\" /><br />\n".
         "</form>\n";
}
else if (isset($_POST['get_amount']) === true &&
         isset($_POST['get_type']) === true &&
         isset($_POST['give_amount']) === true &&
         isset($_POST['give_type']) === true)
{
    require_once("game.inc.php");
    //initialize the function if trading values above are filled in
    $result = placeResourceTrade($_SESSION['user_id'],
                                 $_POST['get_amount'],
                                 $_POST['get_type'],
                                 $_POST['give_amount'],
                                 $_POST['give_type']);

    switch ($result)
    {
    case 0:
        echo "<p>\n".
             " registered\n".
             "</p>\n";
        break;

    case -2:
        echo "<p>\n".
             "  The same resource type ?!\n".
             "</p>\n";
        break;

    case -6:
        echo " <p>\n".
             "  Offered Quantity is too much. \n".
             "</p>\n";
        break;

    default:
        echo "<p>\n".
             " Error \n".
             "</p>\n";
        break;
    }
}
else if (isset($_POST['action']) === true)
{
    require_once("game.inc.php");

    $result = handleResourceTrade($_POST['action'],
                                  $_SESSION['user_id']);

    switch ($result)
    {
    case 1:
        echo "<p>\n".
             "   Deleted \n".
             "</p>\n";
        break;

    case 2:
        echo "<p>\n".
             "   Accepted \n".
             "</p>\n";
        break;

    case -9:
        echo "<p>\n".
             "   Not enough Resources.\n".
             "</p>\n";
        break;

    default:
        echo "<p>\n".
             "  Error \n".
             "</p>\n";
        break;
    }
}
else
{
    // Error in the logic
}

echo "    </body>\n".
     "</html>\n".
     "\n";

?>
