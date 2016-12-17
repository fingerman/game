
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



echo "<div>\n".
    "   <a href=\"overview.php\">Overview</a>\n".
    "  <hr />\n".
    "</div>\n";


if (isset($_POST['opponent']) !== true) {
    echo "<form action=\"attack.php\" method=\"post\">\n".
        " Attack <input name=\"opponent\" type=\"text\" size=\"20\" maxlength=\"40\" /> User Name <br />\n" .
        " <input type=\"submit\" value=\"Send\" /><br />\n" .
        "</form>\n";
}
else
    {
    require_once("game.inc.php");
    $attacker = attackerArmy($_SESSION['user_id']);
    $opponent = opponentArmy($_POST['opponent']);


        if (($attacker['soldiers'] + $attacker['cavaliers']*2) >
            ($opponent['soldiers'] + $opponent['cavaliers']*2)){
            print "You won";

                //To do: function update and deduce number of soldiers in DB
        }
        elseif (($attacker['soldiers'] + $attacker['cavaliers']*2) <
            ($opponent['soldiers'] + $opponent['cavaliers']*2)) {
            print "Unfortunately you lost";
        }
        else{
                print "Wow, noone wins";
            }


    }



echo "    </body>\n".
    "</html>\n".
    "\n";




