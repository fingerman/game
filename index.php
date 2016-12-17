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




session_start();

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
    "<!DOCTYPE html\n".
    "    PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n".
    "    \"http://www.w3.org/TR/xhtml1/DTD/xhtml-strict.dtd\">\n".
    "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n".
    "    <head>\n".
    "        <title>Feudal Lords</title>\n".
    "    </head>\n".
    "    <body>\n";

if (isset($_POST['name']) !== true ||
    isset($_POST['password']) !== true)
{
    echo "<form action=\"index.php\" method=\"post\">\n".
         "  <input name=\"name\" type=\"text\" size=\"20\" maxlength=\"40\" /> Name<br />\n".
         "  <input name=\"password\" type=\"password\" size=\"20\" maxlength=\"40\" /> Password<br />\n".
         "  <input type=\"submit\" value=\"Log in\" /><br />\n".
         "</form>\n";
}
else
{
    require_once("database.inc.php");

    // Here we secure user input
    // javascript, htmlentities, etc.
    //
    //
    $name = mysqli_real_escape_string($mysql_connection, $_POST['name']);


    $user = false;

    if ($name != false &&
        $mysql_connection != false)
    {
        $user = mysqli_query($mysql_connection, "SELECT `id`,\n".
                            "`salt`,\n".
                            "`password`\n".
                            "FROM `user`\n".
                            "WHERE `name` LIKE '".$name."'");
    }

    if ($user != false)
    {
        if (mysqli_num_rows($user) == 0)
        {
            // If not result, then there is no user. So make a new one

            require_once("game.inc.php");

            $user = insertNewUser($name, $_POST['password']);   // def in game.inc

            if ($user > 0)
            {
                $user = array("id" => $user);
            }
            else
            {
                $user = NULL;
            }
        }
        else
        {
            // The user is already in registered,
            // he wants to log in again
            //

            $result = mysqli_fetch_assoc($user);
            mysqli_free_result($user);
            $user = $result;
            //check if the pass entered is the same as the one in the DB
            if ($user['password'] === hash('sha512', $user['salt'].$_POST['password'])) {
                $user = array("id" => $user['id']);
                echo "<div>\n".
                    "Welcome again !\n".
                    "</div><br>\n";
            }
            else
            {
                echo "<p>\n".
                     "Sorry, unfortunately this password is wrong :(\n".
                     "</p>\n".
                     "</body>\n".
                     "</html>\n".
                     "\n";

                exit();
            }
        }
    }

    if (is_array($user) === true)
    {
        // Update the User

        require_once("game.inc.php");

        if (updateUser($user['id']) === 0)
        {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $name;

            echo "<div>\n".
                 "Here you go <a href=\"overview.php\">forward</a>...\n".
                 "</div>\n";
        }
        else
        {
            echo "<p>\n".
                 "Sorry, we could not update your walues. That is why you cannot log in.\n".
                 "</p>\n";
        }
    }
    else
    {
        echo "<p>\n".
             "We cannot access the Data Bank.\n".
             "</p>\n";
    }
}

echo "</body>\n".
     "</html>\n".
     "\n";



?>
