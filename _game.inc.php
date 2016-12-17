<?php




require_once("defines.inc.php");



// prepare $name to use it in SQl
function insertNewUser($name, $password)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }
        //if a query is missing. For instance new user is registered but his resources
        // could not be queried
    if (mysqli_query($mysql_connection, "BEGIN") !== true)
    {
        return -2;
    }

    $salt = md5(uniqid(rand(), true));
    // recode the pass - hash, random unique id
    $password = hash('sha512', $salt.$password);

    if (mysqli_query($mysql_connection, "INSERT INTO `user` (`id`,\n".
                    "`name`,\n".
                    "`salt`,\n".
                    "`password`)\n".
                    "VALUES (NULL,\n".
                    " '".$name."',\n".
                    " '".$salt."',\n".
                    " '".$password."')\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -3;
    }
    //take the id from the last query to initialize the player data
    $id = mysqli_insert_id($mysql_connection);

    if ($id == 0)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -4;
    }



    $fields_left = 24;
    //at least one land, wood or stone as those are essential
    $fields_grass = rand(1, $fields_left - 2);
    $fields_left -= $fields_grass;
    $fields_wood = rand(1, $fields_left - 1);
    $fields_left -= $fields_wood;
    $fields_stone = rand(1, $fields_left);
    $fields_left -= $fields_stone;
    $fields_coal = rand(0, $fields_left);
    $fields_left -= $fields_coal;
    $fields_iron = rand(0, $fields_left);
    $fields_left -= $fields_iron;
    $fields_gold = $fields_left;

    if (mysqli_query($mysql_connection, "INSERT INTO `user_map` (`user_id`,\n".
                    "`fields_grass`,\n".
                    "`fields_wood`,\n".
                    "`fields_stone`,\n".
                    "`fields_coal`,\n".
                    "`fields_iron`,\n".
                    "`fields_gold`)\n".
                    "VALUES (".$id.",\n".
                    " ".$fields_grass.",\n".
                    " ".$fields_wood.",\n".
                    " ".$fields_stone.",\n".
                    " ".$fields_coal.",\n".
                    " ".$fields_iron.",\n".
                    " ".$fields_gold.")\n") !== true)
    {
        mysqli_query($mysql_connection,"ROLLBACK");
        return -5;
    }

    if (mysqli_query($mysql_connection, "INSERT INTO `user_resource` (`user_id`,\n".
                    "`food`,\n".
                    "`wood`,\n".
                    "`stone`,\n".
                    "`coal`,\n".
                    "`iron`,\n".
                    "`gold`)\n".
                    "VALUES (".$id.",\n".
                    "10,\n".
                    "6,\n".
                    "0,\n".
                    "0,\n".
                    "0,\n".
                    "0)\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -6;
    }

    if (mysqli_query($mysql_connection, "COMMIT") === true)
    {
        return $id;
    }

    mysqli_query($mysql_connection, "ROLLBACK");
    return 0;
}

function updateUser($userID)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }


    $messages = array();

    // take ready buildings from build_queue table  to the building table

    $buildQueue = mysqli_query($mysql_connection, "SELECT `building`,\n".
                               "`ready`\n".
                               "FROM `build_queue`\n".
                               "WHERE `user_id`=".$userID." AND\n".
                               "`ready`<CURDATE()\n".
                               "ORDER BY `ready` ASC");

    if ($buildQueue != false)
    {
        $result = array();

        while ($temp = mysqli_fetch_assoc($buildQueue))
        {
            $result[] = $temp;
        }

        mysqli_free_result($buildQueue);
        $buildQueue = $result;
    }
    else
    {
        return -2;
    }


    if (mysqli_query($mysql_connection, "BEGIN") !== true)
    {
        return -3;
    }
    //the transaction has started and we render the buildings in the build_queue
    // timer shows how old is the building - related to resources
    foreach ($buildQueue as $building)
    {
        if (mysqli_query($mysql_connection, "INSERT INTO `building` (`user_id`,\n".
                        "`building`,\n".
                        "`timer`)\n".
                        "VALUES(".$userID.",\n".
                        " '".$building['building']."',\n".
                        " '".$building['ready']."')\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -4;
        }

        $messages[] = translateEnumBuildingToDisplayText($building['building'])." has been finished on the  ".$building['ready'].".";
    }
    // remove the inputs from the build_queue table as teh are ready
    if (mysqli_query($mysql_connection, "DELETE\n".
                    "FROM `build_queue`\n".
                    "WHERE `user_id`=".$userID." AND\n".
                    "`ready`<CURDATE()") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -5;
    }


    // Produce resources from the buildings

    $resources = mysqli_query($mysql_connection, "SELECT `food`,".
                              " `wood`,\n".
                              " `stone`,\n".
                              " `coal`,\n".
                              " `iron`,\n".
                              " `gold`,\n".
                              " `soldiers`,\n".
                              " `cavaliers`\n".
                              "FROM `user_resource`\n".
                              "WHERE `user_id`=".$userID."\n");

    if ($resources != false)
    {
        $result = mysqli_fetch_assoc($resources);
        mysqli_free_result($resources);
        $resources = $result;
    }
    else
    {
        return -6;
    }
    // retrieve the buildings in the build_que which CURDATE is smaller than the ...
    // ... current date
    // evtl. switch out places
    $building = mysqli_query($mysql_connection, "SELECT `building`,\n".
                            " `timer`\n".
                            "FROM `building`\n".
                            "WHERE `user_id`=".$userID." AND\n".
                            " `timer`<CURDATE()");

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
    else
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -7;
    }

    $farms = 0;
    $woodmans = 0;
    $miners = 0;
    $soldiers = 0;
    $cavaliers = 0;

    foreach ($building as $oneBuilding)
    {
        //calculate how many days the buildings are still in the build_queue and are ready
        $dayDiffer = strtotime(date("Y-m-d")) - strtotime($oneBuilding['timer']);
        $dayDiffer = floor($dayDiffer / 86400);

        if ($dayDiffer < 0)
        {
            continue;
        }
        //maybe we have two or three farms ready, so them ready
        switch ($oneBuilding['building'])
        {
        case ENUM_BUILDING_FARM:
            $farms += $dayDiffer;
            break;
        case ENUM_BUILDING_WOODMANS:
            $woodmans += $dayDiffer;
            break;
        case ENUM_BUILDING_MINERS:
            $miners += $dayDiffer;
            break;
        case ENUM_BUILDING_SOLDIER:
            $soldiers += $dayDiffer;
            break;
        case ENUM_BUILDING_CAVALIER:
            $cavaliers += $dayDiffer;
            break;
        }
    }

    $food = $resources['food'];
    $wood = $resources['wood'];
    $stone = $resources['stone'];
    $soldiers = $resources['soldiers'];
    $cavaliers = $resources['cavaliers'];
    // farm brings 2 Food so we multiply it with the number of farms
    $food += 2 * $farms;

    //calculate the resources ...
    for ($i = 0; $i < $woodmans; $i++)
    {
        if ($food > 0)
        {
            $food -= 1;
            $wood += 1;
        }
        else
        {
            break;
        }
    }

    for ($i = 0; $i < $miners; $i++)
    {
        if ($food > 0)
        {
            $food -= 1;
            $stone += 1;
        }
        else
        {
            break;
        }
    }

    for ($i = 0; $i < $soldiers; $i++)
    {
        if ($food > 0)
        {
            $food -= 1;
        }
        else
        {
            break;
        }
    }

    for ($i = 0; $i < $cavaliers; $i++)
    {
        if ($food > 0 && $wood >0)
        {
            $food -= 1;
            $wood -= 1;
        }
        else
        {
            break;
        }
    }
    // ... and put them in the DB
    if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                    "SET `food`=".$food.",\n".
                    " `wood`=".$wood.",\n".
                    " `stone`=".$stone."\n".
                    " `soldiers`=".$soldiers."\n".
                    " `cavaliers`=".$cavaliers."\n".
                    "WHERE `user_id`=".$userID."\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -8;
    }
    //put them in the message
    // deduce the current state from the new, so we have minus or plus
    $messages[] = "Created resources: ".
                  sprintf("%+d", $food - $resources['food'])." Food, ".
                  sprintf("%+d", $wood - $resources['wood'])." Wood, ".
                  sprintf("%+d", $stone - $resources['stone'])." Stone.".
                  sprintf("%+d", $soldiers - $resources['soldiers'])." Soldiers and ".
                  sprintf("%+d", $cavaliers - $resources['cavaliers'])." Cavaliers.";

    // record that the resources have been read out and taken
    if (mysqli_query($mysql_connection, "UPDATE `building`\n".
                    "SET `timer`=CURDATE()\n".
                    "WHERE `user_id`=".$userID." AND\n".
                    "`timer`<CURDATE()") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -9;
    }

    if (mysqli_query($mysql_connection, "COMMIT") !== true)
    {
        return -10;
    }

    // the message comes in this array
    addMessages($userID, $messages);

    return 0;
}

// Return-Values:
// -6 not enough wood
// -7 not enough stone
/**
 * @param $userID
 * @param $building
 * @return int
 */
function insertNewBuilding($userID, $building)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }

//read the fields which the user has
    $map = mysqli_query($mysql_connection, "SELECT `fields_grass`,".
                       " `fields_wood`,\n".
                       " `fields_stone`,\n".
                       " `fields_coal`,\n".
                       " `fields_iron`,\n".
                       " `fields_gold`\n".
                       "FROM `user_map`\n".
                       "WHERE `user_id`=".$_SESSION['user_id']."\n");

    if ($map != false)
    {
        $result = mysqli_fetch_assoc($map);
        mysqli_free_result($map);
        $map = $result;
    }
    else
    {
        return -2;
    }

    $resources = mysqli_query($mysql_connection, "SELECT `food`,".
                              " `wood`,\n".
                              " `stone`,\n".
                              " `coal`,\n".
                              " `iron`,\n".
                              " `gold`\n".
                              "FROM `user_resource`\n".
                              "WHERE `user_id`=".$userID."\n");

    if ($resources != false)
    {
        $result = mysqli_fetch_assoc($resources);
        mysqli_free_result($resources);
        $resources = $result;
    }
    else
    {
        return -3;
    }


    if (mysqli_query($mysql_connection, "BEGIN") !== true)
    {
        return -4;
    }

    switch ($building)
    {
        //check if it is enough "Land", if not break
    case ENUM_BUILDING_FARM:
        if ($map['fields_grass'] <= 0)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -5;
        }
        //check also if the user has enough wood
        if ($resources['wood'] < 3)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -6;
        }
        //if fine, than put it in the build_queue in the  database
        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                        "`building`,\n".
                        "`ready`)\n".
                        "VALUES (".$userID.",\n".
                        " '".$building."',\n".
                        " CURDATE() + 3)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }
        // take Land units out
        if (mysqli_query($mysql_connection, "UPDATE `user_map`\n".
                        "SET `fields_grass`=`fields_grass` - 1\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -101;
        }
        //take Wood units out
        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `wood`=`wood` - 3\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }

        break;
    case ENUM_BUILDING_WOODMANS:
        if ($map['fields_wood'] <= 0)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -5;
        }

        if ($resources['wood'] < 1)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -6;
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                        "`building`,\n".
                        "`ready`)\n".
                        "VALUES (".$userID.",\n".
                        "'".$building."',\n".
                        "CURDATE() + 1)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_map`\n".
                        "SET `fields_wood`=`fields_wood` - 1\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -101;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `wood`=`wood` - 1\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection,"ROLLBACK");
            return -102;
        }

        break;
    case ENUM_BUILDING_MINERS:
        if ($map['fields_stone'] <= 0)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -5;
        }

        if ($resources['wood'] < 2)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -6;
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                        " `building`,\n".
                        " `ready`)\n".
                        "VALUES (".$userID.",\n".
                        " '".$building."',\n".
                        " CURDATE() + 2)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_map`\n".
                        "SET `fields_stone`=`fields_stone` - 1\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -101;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `wood`=`wood` - 2\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }

    case ENUM_BUILDING_SOLDIER:

        if ($resources['food'] < 1)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -6;
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                " `building`,\n".
                " `ready`)\n".
                "VALUES (".$userID.",\n".
                " '".$building."',\n".
                " CURDATE() + 1)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }


        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                "SET `food`=`food` - 1\n".
                "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }

    case ENUM_BUILDING_CAVALIER:

        if ($resources['wood'] < 1)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -6;
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                " `building`,\n".
                " `ready`)\n".
                "VALUES (".$userID.",\n".
                " '".$building."',\n".
                " CURDATE() + 1)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }


        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                "SET `wood`=`wood` - 1,\n".
                "    `stone`=`stone` - 1\n".
                "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }





        break;
    case ENUM_BUILDING_MARKET:
        //check if enough wood (5) and stone (5)
        if ($resources['wood'] < 5)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            // const.
            return -6;
        }

        if ($resources['stone'] < 5)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -7;
        }

        {

            $existing_building = mysqli_query($mysql_connection, "SELECT `building`\n".
                                               "FROM `building`\n".
                                               "WHERE `user_id`=".$userID." AND\n".
                                               "    `building`='".$building."'");

            if ($existing_building == false)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -8;
            }

            if (mysqli_num_rows($existing_building) > 0)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -9;
            }
        }

        {

            $queue_building = mysqli_query($mysql_connection, "SELECT `ready`\n".
                                                "FROM `build_queue`\n".
                                                "WHERE `user_id`=".$userID." AND\n".
                                                "    `building`='".$building."'");

            if ($queue_building == false)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -8;
            }

            if (mysqli_num_rows($queue_building) > 0)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -9;
            }
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                        "`building`,\n".
                        "`ready`)\n".
                        "VALUES (".$userID.",\n".
                        "'".$building."',\n".
                        "CURDATE() + 4)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `wood`=`wood` - 5,\n".
                        "    `stone`=`stone` - 5\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }

        break;
    case ENUM_BUILDING_BARRACKS:
        //check if enough wood (5) and stone (5)
        if ($resources['wood'] < 4)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            // const.
            return -6;
        }

        if ($resources['stone'] < 4)
        {
            mysqli_query($mysql_connection, "ROLLBACK");

            return -7;
        }

        {

            $existing_building = mysqli_query($mysql_connection, "SELECT `building`\n".
                "FROM `building`\n".
                "WHERE `user_id`=".$userID." AND\n".
                "    `building`='".$building."'");

            if ($existing_building == false)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -8;
            }

            if (mysqli_num_rows($existing_building) > 0)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -9;
            }
        }

        {

            $queue_building = mysqli_query($mysql_connection, "SELECT `ready`\n".
                "FROM `build_queue`\n".
                "WHERE `user_id`=".$userID." AND\n".
                "    `building`='".$building."'");

            if ($queue_building == false)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -8;
            }

            if (mysqli_num_rows($queue_building) > 0)
            {
                mysqli_query($mysql_connection, "ROLLBACK");
                return -9;
            }
        }

        if (mysqli_query($mysql_connection, "INSERT INTO `build_queue` (`user_id`,\n".
                "`building`,\n".
                "`ready`)\n".
                "VALUES (".$userID.",\n".
                "'".$building."',\n".
                "CURDATE() + 3)\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -100;
        }

        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                "SET `wood`=`wood` - 4,\n".
                "    `stone`=`stone` - 4\n".
                "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -102;
        }

        break;

    default:
        mysqli_query($mysql_connection, "ROLLBACK");
        return -103;
    }

    if (mysqli_query($mysql_connection, "COMMIT") !== true)
    {
        return -104;
    }

    return 0;
}

// return-values:
// -4 Receiver does not exist
// -6 count is too high
function sendResources($userID, $quantity, $resourceArt, $receiver, $sender = NULL)
{
    if ($quantity == 0)
    {
        return 0;
    }
    //no negative values
    if ($quantity < 0)
    {
        $quantity *= -1;
    }


    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }

    $messages = array();

    // secure the input of user names
    $quantity = mysqli_real_escape_string($mysql_connection, $quantity);
    $resourceArt = mysqli_real_escape_string($mysql_connection, $resourceArt);
    $receiver = mysqli_real_escape_string($mysql_connection, $receiver);

    if ($quantity == false ||
        $resourceArt == false ||
        $receiver == false)
    {
        return -2;
    }
    //check if the receiver exists
    $receiverID = mysqli_query($mysql_connection, "SELECT `id`\n".
                                "FROM `user`\n".
                                "WHERE `name` LIKE '".$receiver."'");

    if ($receiverID == false)
    {
        return -3;
    }

    if (mysqli_num_rows($receiverID) == 1)
    {
        $result = mysqli_fetch_assoc($receiverID);
        mysqli_free_result($receiverID);
        $receiverID = $result['id'];
    }
    else
    {
        // constant value
        return -4;
    }
    //if up to now fine, then check the resources of the user ...
    $resources = mysqli_query($mysql_connection, "SELECT `".$resourceArt."`".
                              "FROM `user_resource`\n".
                              "WHERE `user_id`=".$userID."\n");

    if ($resources != false)
    {
        $result = mysqli_fetch_assoc($resources);
        mysqli_free_result($resources);
        $resources = $result;
    }
    else
    {
        return -5;
    }
    //... and if he has enough of them than ...
    if ($resources[$resourceArt] < $quantity)
    {
        // constant value
        return -6;
    }
    //...start the transaction
    if (mysqli_query($mysql_connection, "BEGIN") !== true)
    {
        return -7;
    }
    // take the resource from the sender and ...
    if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                    "SET `".$resourceArt."`=`".$resourceArt."` - ".$quantity."\n".
                    "WHERE `user_id`=".$userID."\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -8;
    }
    // ... put the resources into the account of the receiver
    if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                    "SET `".$resourceArt."`=`".$resourceArt."` + ".$quantity."\n".
                    "WHERE `user_id`=".$receiverID."\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -9;
    }

    if (is_string($sender) === true)
    {
        $messages[] = $quantity." ".translateEnumResourceToDisplayText($resourceArt)." received from ".$sender.".";
    }
    else
    {
        $messages[] = $quantity." ".translateEnumResourceToDisplayText($resourceArt)." received.";
    }

    if (mysqli_query($mysql_connection, "COMMIT") !== true)
    {
        return -10;
    }

    addMessages($receiverID, $messages);

    return 0;
}

// Return-Values:
// -2 Resource-Types are equal.
// -6 Offer Quantity is too much.
function placeResourceTrade($userID, $seekQuantity, $seekResourceType, $offerQuantity, $offerResourceType)
{
    if ($seekQuantity == 0 &&
        $offerQuantity == 0)
    {
        return -1;
    }

    if ($seekResourceType == $offerResourceType)
    {
        return -2;
    }

    if ($seekQuantity < 0)
    {
        $seekQuantity *= -1;
    }

    if ($offerQuantity < 0)
    {
        $offerQuantity *= -1;
    }


    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -3;
    }

    // security
    $seekQuantity = mysqli_real_escape_string($mysql_connection, $seekQuantity);
    $seekResourceType = mysqli_real_escape_string($mysql_connection, $seekResourceType);
    $offerQuantity = mysqli_real_escape_string($mysql_connection, $offerQuantity);
    $offerResourceType = mysqli_real_escape_string($mysql_connection, $offerResourceType);

    if ($seekQuantity == false ||
        $seekResourceType == false ||
        $offerQuantity == false ||
        $offerResourceType == false)
    {
        return -4;
    }

    $resources = mysqli_query($mysql_connection, "SELECT `".$offerResourceType."`".
                              "FROM `user_resource`\n".
                              "WHERE `user_id`=".$userID."\n");

    if ($resources != false)
    {
        $result = mysqli_fetch_assoc($resources);
        mysqli_free_result($resources);
        $resources = $result;
    }
    else
    {
        return -5;
    }
    //do I have enough resources
    if ($resources[$offerResourceType] < $offerQuantity)
    {
        return -6;
    }

    if (mysqli_query($mysql_connection, "BEGIN") !== true)
    {
        return -7;
    }
    //deduce resources from the account of the user offering
    if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                    "SET `".$offerResourceType."`=`".$offerResourceType."` - ".$offerQuantity."\n".
                    "WHERE `user_id`=".$userID."\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -8;
    }
    //and put them in the  trading table - the market is a kind of holder
    if (mysqli_query($mysql_connection, "INSERT INTO `trading` (`id`,\n".
                    " `give_amount`,\n".
                    " `give_type`,\n".
                    " `get_amount`,\n".
                    " `get_type`,\n".
                    "  `time`,\n".
                    " `user_id`)\n".
                    "VALUES (NULL,\n".
                    "  ".$offerQuantity.",\n".
                    "  '".$offerResourceType."',\n".
                    "  ".$seekQuantity.",\n".
                    "  '".$seekResourceType."',\n".
                    "  NULL,\n".
                    "  ".$userID.")\n") !== true)
    {
        mysqli_query($mysql_connection, "ROLLBACK");
        return -9;
    }


    if (mysqli_query($mysql_connection, "COMMIT") !== true)
    {
        return -10;
    }

    return 0;
}

// return-values:
// -9 the give-Costs are to high (= not enough own Resources).
// 1 Trade deleted
// 2 Trade accepted
function handleResourceTrade($tradeID, $userID)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }
    //secure the form
    $tradeID = mysqli_real_escape_string($mysql_connection, $tradeID);

    if ($tradeID == false)
    {
        return -2;
    }

    $trade = mysqli_query($mysql_connection, "SELECT `give_amount`,\n".
                         " `give_type`,\n".
                         " `get_amount`,\n".
                         " `get_type`,\n".
                         " `user_id`\n".
                         "FROM `trading`\n".
                         "WHERE `id`=".$tradeID."\n");

    if ($trade != false)
    {
        $result = mysqli_fetch_assoc($trade);
        mysqli_free_result($trade);
        $trade = $result;
    }
    else
    {
        return -3;
    }

    if ($trade['user_id'] == $userID)
    {
        // the user wants to delete the trade

        if (mysqli_query($mysql_connection, "BEGIN") !== true)
        {
            return -4;
        }

        // give resources from the Market to the user
        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `".$trade['give_type']."`=`".$trade['give_type']."` + ".$trade['give_amount']."\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -5;
        }
        // Delete the registered Offer from the table
        if (mysqli_query($mysql_connection, "DELETE\n".
                        "FROM `trading`\n".
                        "WHERE `id`=".$tradeID) !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -6;
        }

        if (mysqli_query($mysql_connection, "COMMIT") !== true)
        {
            return -7;
        }

        // constant value
        return 1;
    }
    else
    {
        // The user accepts the offer

        $messages = array();

        $resources = mysqli_query($mysql_connection, "SELECT `".$trade['get_type']."`".
                                  "FROM `user_resource`\n".
                                  "WHERE `user_id`=".$userID."\n");
        //check first the resources of the accepting player
        if ($resources != false)
        {
            $result = mysqli_fetch_assoc($resources);
            mysqli_free_result($resources);
            $resources = $result;
        }
        else
        {
            return -8;
        }

        //check if he has enough resources to give in change
        if ($resources[$trade['get_type']] < $trade['get_amount'])
        {
            // if not break
            return -9;
        }

        if (mysqli_query($mysql_connection, "BEGIN") !== true)
        {
            return -10;
        }

        // if ok, registry the offer into his account and take out the resources,
        // which he gives in change
        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `".$trade['give_type']."`=`".$trade['give_type']."` + ".$trade['give_amount'].",\n".
                        "    `".$trade['get_type']."`=`".$trade['get_type']."` - ".$trade['get_amount']."\n".
                        "WHERE `user_id`=".$userID."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -11;
        }

        // User of the Offer: "give" has been deduced at the time of the transaction.
        // so just delete the trade
        if (mysqli_query($mysql_connection, "UPDATE `user_resource`\n".
                        "SET `".$trade['get_type']."`=`".$trade['get_type']."` + ".$trade['get_amount']."\n".
                        "WHERE `user_id`=".$trade['user_id']."\n") !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -12;
        }

        $messages[] = "Market Notice: Someone has taken ".$trade['give_amount']." ".translateEnumResourceToDisplayText($trade['give_type'])." against ".$trade['get_amount']." ".translateEnumResourceToDisplayText($trade['get_type']).".";

        //Delete the offer from the DB table
        if (mysqli_query($mysql_connection, "DELETE\n".
                        "FROM `trading`\n".
                        "WHERE `id`=".$tradeID) !== true)
        {
            mysqli_query($mysql_connection, "ROLLBACK");
            return -13;
        }

        if (mysqli_query($mysql_connection, "COMMIT") !== true)
        {
            return -14;
        }

        addMessages($trade['user_id'], $messages);

        // const.
        return 2;
    }

    return -15;
}

function addMessages($userID, $messages)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }

    foreach ($messages as $message)
    {
        // security
        $message = mysqli_real_escape_string($mysql_connection, $message);

        if ($message == false)
        {
            continue;
        }

        @mysqli_query($mysql_connection, "INSERT INTO `message` (`id`,\n".
                     "`text`,\n".
                     "`time`,\n".
                     "`user_id`)\n".
                     "VALUES (NULL,\n".
                     "'".$message."',\n".
                     " NULL,\n".
                     "".$userID.")\n");
    }

    return 0;
}

function removeAllMessages($userID)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }

    if (mysqli_query($mysql_connection, "DELETE\n".
                    "FROM `message`\n".
                    "WHERE `user_id`=".$userID) !== true)
    {
        return -2;
    }

    return 0;
}

function attackerArmy($userID)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }
    if ($mysql_connection != false) {
        $resAtt = mysqli_query($mysql_connection, "SELECT `soldiers`," .
            "`cavaliers`\n" .
            "FROM `user_resource`\n" .
            "WHERE `user_id`=" . $_SESSION['user_id'] . "\n");

        if ($resAtt != false) {
            $result = mysqli_fetch_assoc($resAtt);
            mysqli_free_result($resAtt); //memory is free
            $resAtt = $result;

        }
    }
    return $resAtt;
}

function opponentArmy($opponentName)
{
    require_once("database.inc.php");
    global $mysql_connection;

    if ($mysql_connection == false)
    {
        return -1;
    }

    $opponentName = mysqli_real_escape_string($mysql_connection, $opponentName);

    $opponentID = mysqli_query($mysql_connection, "SELECT `id`\n".
        "FROM `user`\n".
        "WHERE `name` LIKE '".$opponentName."'");

    if (mysqli_num_rows($opponentID) == 1)
    {
        $result = mysqli_fetch_assoc($opponentID);
        mysqli_free_result($opponentID);
        $opponentID = $result['id'];
    }

    if ($mysql_connection != false) {
        $resOpp = mysqli_query($mysql_connection, "SELECT `soldiers`," .
            "`cavaliers`\n" .
            "FROM `user_resource`\n" .
            "WHERE `user_id`=".$opponentID."\n");

        if ($resOpp != false) {
            $result = mysqli_fetch_assoc($resOpp);
            mysqli_free_result($resOpp); //memory is free
            $resOpp = $result;

        }
    }
    return $resOpp;
}





?>
