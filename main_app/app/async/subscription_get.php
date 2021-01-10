<?php
session_start();

header('Content-type: application/json');

include_once '../db_scripts/Models/Users.php';
include_once '../db_scripts/Models/Movies.php';
include_once('../db_scripts/keyrock_api.php');
include_once('../db_scripts/orion_api.php');
include_once('../Utils/Random.php');
include_once('../Utils/Logs.php');

logger("-- In Get subscriptions");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login']) && $_SESSION['login'] === true )
{
    // User already logged in...
    logger("User: " . $_SESSION['user_username']);
    logger("Role: " . $_SESSION['user_role']);

    ?>
    <table id="movies-table">
        <tr>
            <th>Movie Title</th>
            <th>Date of Interest</th>
            <th>Delete</th>
        </tr>

        <?php
        $subscriptions = Orion_API::GetAllUserSubscriptionsFromDB($_SESSION['user_id']);
        foreach ($subscriptions as $sub)
        {
            $movie_id = $sub['movie_id'];
            ?>
            <tr id="sub_<?php echo $movie_id?>">
                <td><div><span id="sub_<?php echo $movie_id?>_title" ><?php echo $sub['movie_title']?></span></div></td>
                <td><div><span id="sub_<?php echo $movie_id?>_date"  ><?php echo $sub['date']?></span></div></td>
                <td class="action-td">
                    <div><button id="sub_<?php echo $movie_id?>_delete" class="btn-primary btn-danger" onclick="deleteSub('<?php echo $movie_id?>')" >Delete</button></div>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>
    <?php
    exit();
}

// If failed for any reason...
echo json_encode(false);


