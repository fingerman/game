<?php
/* Copyright (C) 2011-2012  Stephan Kreutzer
 *
 * This file is part of Tutorial "Tabellen-Browsergames mit PHP".
 *
 * Tutorial "Tabellen-Browsergames mit PHP" is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License version 3 only,
 * as published by the Free Software Foundation.
 *
 * Tutorial "Tabellen-Browsergames mit PHP" is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License 3 for more details.
 *
 * You should have received a copy of the GNU Affero General Public License 3
 * along with Tutorial "Tabellen-Browsergames mit PHP". If not, see <http://www.gnu.org/licenses/>.
 */



//define constants to use in the DB
define("ENUM_BUILDING_FARM", "FARM");
define("ENUM_BUILDING_WOODMANS", "WOODMANS");
define("ENUM_BUILDING_MINERS", "MINERS");
define("ENUM_BUILDING_MARKET", "MARKET");
define("ENUM_BUILDING_BARRACKS", "BARRACKS");
define("ENUM_BUILDING_SOLDIER", "SOLDIER");
define("ENUM_BUILDING_CAVALIER", "CAVALIER");

define("ENUM_RESOURCE_FOOD", "FOOD");
define("ENUM_RESOURCE_WOOD", "WOOD");
define("ENUM_RESOURCE_STONE", "STONE");
define("ENUM_RESOURCE_COAL", "COAL");
define("ENUM_RESOURCE_IRON", "IRON");
define("ENUM_RESOURCE_GOLD", "GOLD");



function translateEnumBuildingToDisplayText($building)
{
    $building = strtoupper($building);

    switch ($building)
    {

        case ENUM_BUILDING_FARM:
        return "Farm";

        case ENUM_BUILDING_WOODMANS:
        return "Wood Mill";

        case ENUM_BUILDING_MINERS:
        return "Stone Quarry";

        case ENUM_BUILDING_MARKET:
        return "Market";

        case ENUM_BUILDING_BARRACKS;
            return "Barracks";

        case ENUM_BUILDING_SOLDIER;
            return "Soldiers";

        case ENUM_BUILDING_CAVALIER;
            return "Cavaliers";

        default:
        return "Unknown";

    }


    return "Unknown";
}

function translateEnumResourceToDisplayText($resourceArt)
{
    $resourceArt = strtoupper($resourceArt);

    switch ($resourceArt)
    {
    case ENUM_RESOURCE_FOOD:
        return "Food";
    case ENUM_RESOURCE_WOOD:
        return "Wood";
    case ENUM_RESOURCE_STONE:
        return "Stone";
    case ENUM_RESOURCE_COAL:
        return "Coal";
    case ENUM_RESOURCE_IRON:
        return "Iron";
    case ENUM_RESOURCE_GOLD:
        return "Gold";
    default:
        return "Unknown";
    }

    return "Unknown";
}



?>
