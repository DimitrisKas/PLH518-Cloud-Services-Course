<?php

function connect(): \MongoDB\Database
{
    $databaseName = "Project2";


    $user = "root";
    $pwd = "1234securePass";

    $m = new MongoDB\Client("mongodb://${user}:${pwd}@mongo:27017");
    return $m->selectDatabase($databaseName);
}


