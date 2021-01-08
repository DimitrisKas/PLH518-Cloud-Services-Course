<?php
session_start();
header('Content-type: application/json');
include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/Models/Favorites.php';
include_once('../db_scripts/keyrock_api.php');
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In ADD/DELETE Favorite");

// Check if User is logged in
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    $data = json_decode(file_get_contents('php://input'), true);
    If (isset($data['movie_id']))
    {
        logger("Is favorite: " .$data['addFavorite']);
        if ($data['addFavorite'] == "true")
            $success_flag = User::AddFavorite($_SESSION['user_id'], $data['movie_id']);
        else
            $success_flag = User::DeleteFavorite($_SESSION['user_id'], $data['movie_id']);

        echo json_encode($success_flag);
        exit();
    }
}

// If failed for any reason...
echo json_encode(false);


