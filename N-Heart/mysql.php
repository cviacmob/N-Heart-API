<?php

function connect_db()
	{
	$server = 'nheart.cviac.com'; // this may be an ip address instead
	$user = 'aramesh';
	$pass = 'cvi@c2016';
	$database = 'nheartdb'; // name of your database
	$connection = new mysqli($server, $user, $pass, $database);
	return $connection;
	}

?>
