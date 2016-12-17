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
         "Please first log in !\n".
         "</div>\n".
         "</body>\n".
         "</html>\n";

    exit();
}


require_once("defines.inc.php");
require_once("database.inc.php");

$resources = false;
//read the resources of the user and initialize the $resources
if ($mysql_connection != false)
{
    $resources = mysqli_query($mysql_connection, "SELECT `food`,".
                              "`wood`,\n".
                              "`stone`,\n".
                              "`coal`,\n".
                              "`iron`,\n".
                              "`gold`,\n".
                              "`soldiers`,\n".
                              "`cavaliers`\n".
                              "FROM `user_resource`\n".
                              "WHERE `user_id`=".$_SESSION['user_id']."\n");
}

if ($resources != false)
{
    $result = mysqli_fetch_assoc($resources);
    mysqli_free_result($resources); //memory is free
    $resources = $result;
}

$map = false;

if ($mysql_connection != false)
{
    $map = mysqli_query($mysql_connection, "SELECT `fields_grass`,".
                       "`fields_wood`,\n".
                       "`fields_stone`,\n".
                       "`fields_coal`,\n".
                       "`fields_iron`,\n".
                       "`fields_gold`\n".
                       "FROM `user_map`\n".
                       "WHERE `user_id`=".$_SESSION['user_id']."\n");
}

if ($map != false)
{
    $result = mysqli_fetch_assoc($map);
    mysqli_free_result($map);
    $map = $result;
}

// TODO: Evtl. with COUNT...
$building = false;

if ($mysql_connection != false)
{
    $building = mysqli_query($mysql_connection, "SELECT `building`".
                            "FROM `building`\n".
                            "WHERE `user_id`=".$_SESSION['user_id']);
}

if ($building != false)
{
    $result = array();

    while ($temp = mysqli_fetch_assoc($building))
    {
        $result[] = $temp;
    }

    mysqli_free_result($building);
    $building = $result;
}

$farms = 0;
$woodmans = 0;
$miners = 0;
$soldier = 0;
$cavalier = 0;
$market = false;
$barracks = false;

if (is_array($building) === true)
{
    foreach ($building as $g)
    {
        switch ($g['building'])
        {
        case ENUM_BUILDING_FARM:
            $farms += 1;
            break;
        case ENUM_BUILDING_WOODMANS:
            $woodmans += 1;
            break;
        case ENUM_BUILDING_MINERS:
            $miners += 1;
            break;
        case ENUM_BUILDING_SOLDIER:
            $soldier += 1;
            break;
        case ENUM_BUILDING_CAVALIER:
            $cavalier += 1;
            break;
        case ENUM_BUILDING_MARKET:
            $market = true;
            break;
        case ENUM_BUILDING_BARRACKS:
            $barracks = true;
            break;
        }
    }
}

//the message emerges at each log in
$messages = false;

if ($mysql_connection != false)
{
    $messages = mysqli_query($mysql_connection, "SELECT `text`".
                            "FROM `message`\n".
                            "WHERE `user_id`=".$_SESSION['user_id']."\n".
                            "ORDER BY `time` ASC");
}

if ($messages != false)
{
    $result = array();

    while ($temp = mysqli_fetch_assoc($messages))
    {
        $result[] = $temp;
    }

    mysqli_free_result($messages);
    $messages = $result;
}



echo "<table border=\"1\">\n";

if (is_array($resources) === true)
{
    echo "<tr>\n".
         "<td colspan=\"2\">\n".
         "Food: ".$resources['food'].",\n".
         "Wood: ".$resources['wood'].",\n".
         "Stone: ".$resources['stone'].",\n".
         "Coal: ".$resources['coal'].",\n".
         "Iron: ".$resources['iron'].",\n".
         "Gold: ".$resources['gold'].",\n".
         "Soldiers: ".$resources['soldiers'].",\n".
         "Cavaliers: ".$resources['cavaliers'].".\n".
         "</td>\n".
         "</tr>\n";
}

echo "<tr>\n".
     "<td>\n";

if (is_array($map) === true)
{
    echo "Land: ".$map['fields_grass']."<br />\n".
         "Woods: ".$map['fields_wood']."<br />\n".
         "Mountains: ".$map['fields_stone']."<br />\n".
         "Coal sources: ".$map['fields_coal']."<br />\n".
         "Iron sources: ".$map['fields_iron']."<br />\n".
         "Gold sources: ".$map['fields_gold']."<br />\n".
         "<br />\n";
}

echo "Farms: ".$farms."<br />\n".
     "Wood Mill: ".$woodmans."<br />\n".
     "Stone Quarry: ".$miners."<br />\n".


     /*
      * not yet implemented
     "Coal miners: <br />\n".
     "Iron mines: <br />\n".
     "Gold mines: <br />\n".
     */
     "              <br />\n";

if ($market === true)
{
    echo "Market <br />\n";
}

if ($barracks === true)
{
    echo "Barracks <br />\n";

}



/*
if ($garrison === true)
{
    echo "Garrison.<br />\n";
}

echo "<br />\n".
     "Castle: <br />\n".
     "Solders: <br />\n".
*/

//menu right
echo "</td>\n".
     "<td valign=\"top\">\n".
     "<a href=\"build.php\">Build</a><br />\n".
     "<a href=\"index.php\">Log Out</a><br />\n".
     "<br>\n";


if (is_array($building) === true)
{
    foreach ($building as $g)
    {
        //if a market is present -> two buttons
        if ($g['building'] == ENUM_BUILDING_MARKET)
        {
            echo "<a href=\"send.php\">Send</a><br />\n".
                 "<a href=\"trade.php\">Trade</a><br />\n";

            break;
        }
    }
}

if (is_array($building) === true)
{
    foreach ($building as $g)
    {
        //if a market is present -> two buttons
        if ($g['building'] == ENUM_BUILDING_BARRACKS)
        {
            echo "<br />\n".
                 "<a href=\"attack.php\">Attack</a><br />\n";

            break;
        }
    }
}






echo "    </td>\n".
     "</tr>\n".
     "<tr>\n".
     "  <td colspan=\"2\">\n";

if (is_array($messages) === true)
{
    foreach ($messages as $message)
    {
        echo " ".$message['text']."<br />\n";
    }

    require_once("game.inc.php");

    // Messages have been already seen
    removeAllMessages($_SESSION['user_id']);
}

echo "</td>\n".
     "</tr>\n".
     "</table>\n".
     "</body>\n".
     "</html>\n".
     "\n";



?>
