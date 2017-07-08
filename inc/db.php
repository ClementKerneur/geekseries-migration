<?php
$dbConfig = json_decode(file_get_contents(dirname(__FILE__)."/../config/db.json"));

//Connect to DB
$mysqli = new mysqli($dbConfig->host, $dbConfig->username, $dbConfig->password, $dbConfig->dbname, $dbConfig->port);
$mysqli->set_charset('utf8');