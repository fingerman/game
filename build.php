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

$map = false;

if ($mysql_connection != false)
{
    $map = mysqli_query($mysql_connection, "SELECT `fields_grass`,".
                       " `fields_wood`,\n".
                       " `fields_stone`,\n".
                       " `fields_coal`,\n".
                       " `fields_iron`,\n".
                       " `fields_gold`\n".
                       "FROM `user_map`\n".
                       "WHERE `user_id`=".$_SESSION['user_id']."\n");
}

if ($map != false)
{
    $result = mysqli_fetch_assoc($map);
    mysqli_free_result($map);
    $map = $result;
}

$building = false;

if ($mysql_connection != false)
{
    $building = mysqli_query($mysql_connection, "SELECT `building`".
                            "FROM `building`\n".
                            "WHERE `user_id`=".$_SESSION['user_id']." AND\n".
                            "`building`='".ENUM_BUILDING_MARKET."'");
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

$buildQueue = false;

if ($mysql_connection != false)
{
    $buildQueue = mysqli_query($mysql_connection, "SELECT `building`".
                               "FROM `build_queue`\n".
                               "WHERE `user_id`=".$_SESSION['user_id']." AND\n".
                               " `building`='".ENUM_BUILDING_MARKET."'");
}

if ($buildQueue!= false)
{
    $result = array();

    while ($temp = mysqli_fetch_assoc($buildQueue))
    {
        $result[] = $temp;
    }

    mysqli_free_result($buildQueue);
    $buildQueue = $result;
}


if (isset($_POST['building']) !== true)
{
    echo "<p>\n".
         "  &nbsp;&nbsp;&nbsp;   Time &ndash; Cost &ndash; Production &ndash; Operating Costs\n".
         "</p>\n".
         "  <form action=\"build.php\" method=\"post\">\n".
         "<p>\n".
         "Land:\n".
         "</p>\n";
//if not enough fields -> no input
    if (is_array($map) === true)
    {
        if ((int) $map['fields_grass'] > 0)
        {
            echo "<input type=\"radio\" name=\"building\" value=\"".ENUM_BUILDING_FARM."\" /> Farm (3 Days &ndash; 3 Wood &ndash; 2 Food &ndash; none).<br />\n";
        }

        if ((int) $map['fields_wood'] > 0)
        {
            echo "<input type=\"radio\" name=\"building\" value=\"".ENUM_BUILDING_WOODMANS."\" /> Wood Mill (1 Day &ndash; 1 Wood &ndash; 1 Wood &ndash; 1 Food).<br />\n";
        }

        if ((int) $map['fields_stone'] > 0)
        {
            echo "<input type=\"radio\" name=\"building\" value=\"".ENUM_BUILDING_MINERS."\" /> Stone Mine (2 Days &ndash; 2 Wood &ndash; 1 Stone &ndash; 1 Food).<br />\n";
        }
    }

    echo "<p>\n".
         " Town:\n".
         "</p>\n";

    if (is_array($building) === true &&
        is_array($buildQueue) === true) {
        $marketOffer = true;

        //first check if it is present in the building and building_queue tables
        foreach ($building as $g) {
            if ($g['building'] == ENUM_BUILDING_MARKET) {
                $marketOffer = false;
                break;
            }
        }

        foreach ($buildQueue as $b) {
            if ($b['building'] == ENUM_BUILDING_MARKET)// ||
                //    $marketOffer == false)
            {
                $marketOffer = false;
                break;
            }
        }

        if ($marketOffer === true) {
            echo " <input type=\"radio\" name=\"building\" value=\"" . ENUM_BUILDING_MARKET . "\" /> Market (4 Days &ndash; 5 Wood, 5 Stone &ndash; Trading &ndash; none)<br />\n";
        }
    }
    if (is_array($building) === true &&
        is_array($buildQueue) === true) {

        // build barracks.
        //first check if it is already built or in the building queue
        $barracksOffer = true;

        //first check if it is present in the building and building_queue tables
        foreach ($building as $d)
        {
            if ($d['building'] == ENUM_BUILDING_BARRACKS)
            {
                $barracksOffer = false;
                break;
            }
        }

        foreach ($buildQueue as $f)
        {
            if ($f['building'] == ENUM_BUILDING_BARRACKS)
            {
                $barracksOffer = false;
                break;
            }
        }

        if ($barracksOffer === true)
        {
            echo " <input type=\"radio\" name=\"building\" value=\"".ENUM_BUILDING_BARRACKS."\" /> Barracks (3 Days &ndash; 4 Wood, 4 Stone &ndash; Army &ndash; none)<br />\n";
        }

    }

    echo " <input type=\"submit\" value=\"Send\" /><br />\n".
         " </form>\n";
}
else
{
    require_once("game.inc.php");

    $result = insertNewBuilding($_SESSION['user_id'], $_POST['building']);

    switch ($result)
    {
    case 0:
        echo "<p>\n".
             "  Building...\n".
             "</p>\n";
        break;

    case -6:
        echo "<p>\n".
             "  Not enough wood.\n".
             "</p>\n";
        break;

    case -7:
        echo "<p>\n".
             "  Not enough Stone.\n".
             "</p>\n";
        break;

    default:
        echo "<p>\n".
             "  Something went wrong.\n".
             "</p>\n";
        break;
    }
}

echo "    </body>\n".
     "</html>\n".
     "\n";



?>
