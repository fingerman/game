<?php





$mysql_connection = new mysqli('localhost', 'root', '', 'game');

if ($mysql_connection->connect_error) die($mysql_connection->connect_error);