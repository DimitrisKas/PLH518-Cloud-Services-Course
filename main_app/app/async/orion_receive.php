<?php
session_start();

include_once('../db_scripts/keyrock_api.php');
include_once('../db_scripts/orion_api.php');
include_once('../Utils/Logs.php');

logger(" -- Notification received from Orion");
$_POST = json_decode(file_get_contents('php://input'), true);

$user_id = $_GET['user'];
$movie_id = $_POST['data'][0]['id'];
$reason = $_GET['reason'];
$reason = $_GET['reason'];


if ($reason == 'date')
{
    $date_of_interest = $_GET['date'];
    Orion_API::SaveNotification($user_id, $movie_id, $reason, $date_of_interest);
}
else
{
    Orion_API::SaveNotification($user_id, $movie_id, $reason);
}

