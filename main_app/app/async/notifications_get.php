<?php
session_start();
include_once '../db_scripts/keyrock_api.php';
include_once '../db_scripts/orion_api.php';
include_once('../Utils/Logs.php');

logger("-- In Notification Get");

// Check if User is logged in AND is an Admin
if (isset($_SESSION['login'])
    && $_SESSION['login'] === true)
{
    // User already logged in...
    $notifications = Orion_API::GetAndDismissAllNotifications($_SESSION['user_id']);

    foreach($notifications as $notif_doc)
    {

        if ($notif_doc['reason'] == 'date')
            $text = "The movie you subscribed at is availaible on ". $notif_doc['date_of_interest'];
        else
            $text = "The movie you subscribed at is no logner available";

        ?>

        <div id="not_<?php echo $notif_doc['movie_id'] ?>" class="notif-cont">
            <h6 class="notif-title"><?php echo $notif_doc['movie_title'];?></h6>
            <p class="notif-text"><?php echo $text;?></p>
            <button class="dismiss-btn btn-primary btn-danger" onclick="dismissNotification('not_<?php echo $notif_doc['movie_id'] ?>')">X</button>
        </div>

        <?php
    }


    exit(1);
}

// If failed for any reason...
echo json_encode(false);


