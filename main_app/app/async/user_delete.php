<?php
session_start();
header('Content-type: application/json');

include_once '../db_scripts/Models/Users.php';
include_once('../db_scripts/keyrock_api.php');
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Delete user");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true
    && isset($_SESSION['user_role'])
    && $_SESSION['user_role'] === User::ADMIN)
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    $data = json_decode(file_get_contents('php://input'), true);

    If (isset($data['user_id']))
    {
        $success_flag = User::DeleteUser($data['user_id']);
        echo json_encode($success_flag);
        exit();
    }
}

// If failed for any reason...
echo json_encode(false);
exit();

